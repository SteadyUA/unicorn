<?php

namespace SteadyUa\Unicorn\Command;

use Composer\Command\BaseCommand;
use Composer\Util\Platform;
use SteadyUa\Unicorn\Provider;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BuildCommand extends BaseCommand
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
            ->setName('uni:build')
            ->setDescription('Builds a local package in the specified directory')
            ->setDefinition(
                [
                    new InputArgument('package', InputArgument::REQUIRED, 'Package name to be installed'),
                    new InputArgument('directory', InputArgument::REQUIRED, 'Directory where the files should be created'),
                ]
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $packageName = $input->getArgument('package');
        foreach ($this->provider->localRepo()->getRepositories() as $repo) {
            $package = $repo->findPackage($packageName, '*');
            if ($package) {
                break;
            }
        }
        if (!isset($package)) {
            $output->writeln('<error>Not found: ' . $packageName . '</error>');
            return self::FAILURE;
        }
        $dir = $input->getArgument('directory');

        $config = $repo->getRepoConfig();
        $config['options']['symlink'] = false;
        $repository = '--repository \'' . json_encode($config) . "'";

        //TODO read dot-env?
        $cmd = 'composer create-project ' . $packageName . ' ' . $dir . ' --no-install ' . $repository;
        $cmd .= ' && rm -rf ' . $dir . '/vendor ' . $dir . '/composer.lock';
        $cmd .= ' && composer install ' . $this->provider->getBuildInstallOptions() . ' -d ' . $dir;

        Platform::putEnv('UNI_BUILD', '1');
        Platform::putEnv('UNI_PATH', $this->provider->unicornJsonFile());

        return passthru($cmd) === false
            ? self::FAILURE
            : self::SUCCESS;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
    }
}
