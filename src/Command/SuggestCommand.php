<?php

namespace SteadyUa\Unicorn\Command;

use Composer\Command\BaseCommand;
use SteadyUa\Unicorn\Provider;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SuggestCommand extends BaseCommand
{

    /** @var Provider */
    private $provider;

    public function __construct(Provider $provider)
    {
        $this->provider = $provider;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('uni:suggest')
            ->setDescription('Suggest package by namespace.')
            ->addArgument('ns', InputArgument::IS_ARRAY|InputArgument::REQUIRED, 'namespace for search.')
        ;
    }

    private function match($autoload, $searchData)
    {
        if (!isset($autoload['psr-4'])) {
            return false;
        }
        foreach ($autoload['psr-4'] as $ns => $path) {
            foreach ($searchData as $symbolName => $symbolLen) {
                $nsLen = strlen($ns);
                $nsLower = strtolower($ns);
                if ($nsLen > $symbolLen) {
                    if (substr($nsLower, 0, $symbolLen) == $symbolName) {
                        return $ns;
                    }
                } elseif (substr($symbolName, 0, $nsLen) == $nsLower) {
                    return $ns;
                }
            }
        }

        return false;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $toFind = $input->getArgument('ns');
        $searchData = [];
        foreach ($toFind as $ns) {
            $searchData[strtolower($ns)] = strlen($ns);
        }

        $data = [];

        $repository = $this->provider->uniComposer()->getRepositoryManager()->getLocalRepository();
        foreach ($repository->getPackages() as $package) {
            if ($ns = $this->match($package->getAutoload(), $searchData)) {
                $link = $package->getSourceUrl() ?? $package->getHomepage() ?? '';
                if ($link !== '') {
                    $path = '<href='.OutputFormatter::escape($link).'>'. $link .'</>';
                } else {
                    $path = $package->getDistType() == 'path'
                        ? $this->provider->relative($package->getDistUrl(), true)
                        : $package->getDistUrl();
                }
                $data += [$ns => [$package->getName(), $ns, $path]];
            }
        }
        if (empty($data)) {
            $output->writeln('Nothing found.');
            return self::FAILURE;
        }

        usort($data, function ($left, $right) {
            return $left[0] <=> $right[0];
        });

        // show list
        $renderer = new Table($output);
        $renderer->setStyle('compact');
        $rendererStyle = $renderer->getStyle();
        $rendererStyle->setCellRowContentFormat('%s  ');
        $renderer->setHeaders(['Package', 'Namespace', 'Path'])
            ->setRows($data)
            ->render();

        return self::SUCCESS;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
    }
}
