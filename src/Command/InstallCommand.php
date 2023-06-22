<?php

namespace SteadyUa\Unicorn\Command;

use Composer\Command\BaseCommand;
use SteadyUa\Unicorn\Provider;
use SteadyUa\Unicorn\Utils;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InstallCommand extends BaseCommand
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
            ->setName('uni:install')
            ->setDescription('Install monorepo packages.')
            ->setDefinition([
                new InputArgument(
                    'packages',
                    InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
                    'Optional package name'
                ),
            ])
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $localRepo = $this->provider->localRepo();
        $packagesArg = $input->getArgument('packages');
        $packages = [];
        if (!empty($packagesArg)) {
            foreach ($packagesArg as $packageName) {
                $package = $localRepo->findPackage($packageName, '*');
                if (!isset($package)) {
                    $output->writeln('<error>Not found: ' . $packageName . '</error>');
                    continue;
                }
                $packages[] = $package;
            }
        } else {
            $packages = $localRepo->getPackages();
        }
        if (count($packages) == 0) {
            return self::SUCCESS;
        }

        $output->writeln('<info>Packages: ' . count($packages) . '</info>');
        if ($output->isVerbose()) {
            foreach ($packages as $package) {
                $output->writeln($package->getName() . "\t" . $package->getDistUrl());
            }
        }

        $utils = new Utils($this->getIO(), $output);

        return $utils->install($packages);
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
    }
}
