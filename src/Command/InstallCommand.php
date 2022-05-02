<?php

namespace SteadyUa\Unicorn\Command;

use Composer\Command\BaseCommand;
use Composer\Util\Platform;
use SteadyUa\Unicorn\Provider;
use SteadyUa\Unicorn\Utils;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class InstallCommand extends BaseCommand
{
    private $provider;

    public const OPTION_FORCE = 'force';
    public const OPTION_ALL = 'all';
    public const OPTION_COPY = 'copy';

    public function __construct(Provider $provider)
    {
        $this->provider = $provider;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('uni:install')
            ->setAliases(['uni:i'])
            ->setDescription('Install monorepo packages.')
            ->setDefinition([
                new InputArgument(
                    'packages',
                    InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
                    'Optional package name'
                ),
                new InputOption(self::OPTION_FORCE, 'f', InputOption::VALUE_NONE, 'Cleanup vendors and locks.'),
                new InputOption(self::OPTION_ALL, 'a', InputOption::VALUE_NONE, 'Run install for all local packages.'),
                new InputOption(
                    self::OPTION_COPY,
                    '',
                    InputOption::VALUE_NONE,
                    'Packages will be copied instead of symlinks.'
                ),
                new InputOption(
                    'no-scripts',
                    null,
                    InputOption::VALUE_NONE,
                    'Whether to prevent execution of all defined scripts.'
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
        } elseif ($input->getOption(self::OPTION_ALL)) {
            if ($input->getOption(self::OPTION_COPY)) {
                $output->writeln('<error>Copy mode is only allowed when packages are specified.</error>');
                return self::FAILURE;
            }
            $packages = $localRepo->getPackages();
        }
        if (count($packages) == 0) {
            return self::SUCCESS;
        }

        $isForce = $input->getOption(self::OPTION_FORCE);
        $output->writeln('<info>Packages: ' . count($packages) . '</info>');
        if ($isForce) {
            $output->writeln('<info>Clean vendors and locks</info>');
        }

        $installOptions = '';
        if ($input->getOption(self::OPTION_COPY)) {
            Platform::putEnv('UNI_COPY', '1');
            $installOptions = $this->provider->getCopyInstallOptions();
        }
        if ($input->getOption('no-scripts')) {
            $installOptions .= ' --no-scripts';
        }
        $utils = new Utils($this->getIO(), $output);

        return $utils->install($packages, $isForce, $installOptions);
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
    }
}
