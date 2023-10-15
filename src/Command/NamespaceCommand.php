<?php

namespace SteadyUa\Unicorn\Command;

use Composer\Command\BaseCommand;
use SteadyUa\Unicorn\Provider;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class NamespaceCommand extends BaseCommand
{
    private Provider $provider;

    public function __construct(Provider $provider)
    {
        $this->provider = $provider;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('uni:namespace')
            ->setDescription('Suggest package by namespace pattern.')
            ->addArgument('query', InputArgument::IS_ARRAY|InputArgument::REQUIRED, 'Namespace pattern for search.')
        ;
    }

    private function match($autoload, $searchData)
    {
        if (!isset($autoload['psr-4'])) {
            return false;
        }
        foreach ($autoload['psr-4'] as $ns => $path) {
            foreach ($searchData as $symbolName) {
                if (fnmatch($symbolName, $ns, FNM_NOESCAPE | FNM_CASEFOLD)) {
                    return $ns;
                }
            }
        }

        return false;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $toFind = $input->getArgument('query');
        $searchData = !is_array($toFind) ? [$toFind] : $toFind;

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
                $data[] = [$ns, $package->getName(), $path];
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
        $renderer->setHeaders(['Namespace', 'Package', 'Path'])
            ->setRows($data)
            ->render();

        return self::SUCCESS;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
    }
}
