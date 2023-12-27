<?php

namespace SteadyUa\Unicorn\Command;

use Composer\Command\BaseCommand;
use Composer\Util\Platform;
use SteadyUa\Unicorn\Provider;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
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
                    new InputOption(
                        'force',
                        'f',
                        InputOption::VALUE_NONE,
                        'Remove target directory if exists.'
                    ),
                    new InputOption(
                        'env',
                        'e',
                        InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
                        'Set an environment variable (can be used multiple times)'
                    ),
                    new InputOption(
                        'env-file',
                        null,
                        InputOption::VALUE_OPTIONAL,
                        'Specify an environment file'
                    )
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

        if ($envFile = $input->getOption('env-file')) {
            foreach (parse_ini_file($envFile) as $key => $value) {
                Platform::putEnv($key, (string) $value);
            }
        }
        if ($env = $input->getOption('env')) {
            foreach ($env as $keyVal) {
                if ($eqPos = strpos($keyVal, '=')) {
                    Platform::putEnv(
                        substr($keyVal, 0, $eqPos),
                        substr($keyVal, $eqPos + 1)
                    );
                }
            }
        }
        $cmdList = [];
        if (file_exists($dir) && $input->getOption('force')) {
            $cmdList[] = 'rm -rf ' . $dir;
        }
        $cmdList[] = 'composer create-project ' . $packageName . ' ' . $dir . ' --no-install ' . $repository;
        $cmdList[] = 'rm -rf ' . $dir . '/vendor ' . $dir . '/composer.lock';
        $cmdList[] = 'composer install ' . $this->provider->getBuildInstallOptions() . ' -d ' . $dir;

        Platform::putEnv('UNI_BUILD', '1');
        Platform::putEnv('UNI_PATH', $this->provider->unicornJsonFile());

        return passthru(implode(' && ', $cmdList)) === false
            ? self::FAILURE
            : self::SUCCESS;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
    }
}
