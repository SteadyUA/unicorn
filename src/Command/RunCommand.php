<?php

namespace SteadyUa\Unicorn\Command;

use Composer\Command\BaseCommand;
use SteadyUa\Unicorn\Provider;
use SteadyUa\Unicorn\Utils;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RunCommand extends BaseCommand
{
    private $provider;

    public function __construct(Provider $provider)
    {
        $this->provider = $provider;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('uni:run-script')
            ->setAliases(['uni:run'])
            ->setDescription('Runs the scripts defined in unicorn.json, for all packages dependent on the current.')
            ->setDefinition(
                [
                    new InputArgument('script', InputArgument::IS_ARRAY, 'Script name to run.'),
                    new InputOption('self', 's', InputOption::VALUE_NONE, 'Also run script for the current package.'),
                    new InputOption(
                        'recursive',
                        'r',
                        InputOption::VALUE_NONE,
                        'Recursively resolves depends up to the root.'
                    ),
                    new InputOption('all', 'a', InputOption::VALUE_NONE, 'Run for all local packages.'),
                    new InputOption('list', 'l', InputOption::VALUE_NONE, 'List scripts.'),
                ]
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $utils = new Utils($this->getIO(), $output);
        if ($input->getOption('list')) {
            return $this->listScripts($utils);
        }

        $localRepo = $this->provider->localRepo();
        $scriptArg = $input->getArgument('script');
        if (empty($scriptArg)) {
            throw new \InvalidArgumentException('script argument not specified.');
        }
        $runScripts = [];
        foreach ($scriptArg as $scenario) {
            $list = $this->provider->getScripts()[$scenario] ?? null;
            if (!isset($list)) {
                $output->writeln($scenario . ' - not found in unicorn.json');
                return self::FAILURE;
            }
            $runScripts = array_merge($runScripts, $list);
        }
        $runScripts = array_unique($runScripts);

        $depends = [];
        if ($input->getOption('all')) {
            foreach ($localRepo->getPackages() as $package) {
                $depends[$package->getName()] = $package;
            }
        } else {
            $package = $this->provider->composer()->getPackage();
            if ($package->getName() == '__root__') {
                $output->writeln('Could not find a composer.json file in ' . getcwd());
                return self::FAILURE;
            }
            if (!$localRepo->findPackage($package->getName(), '*')) {
                $output->writeln('Only available for local packages.');
                return self::FAILURE;
            }
            $depends = $this->provider->getDepends(
                $package,
                $input->getOption('recursive')
            );
            if ($input->getOption('self')) {
                $depends[$package->getName()] = $package;
            }
        }

        return $utils->runScripts($runScripts, $depends);
    }

    protected function listScripts(Utils $utils): int
    {
        $scripts = $this->provider->getScripts();

        if (!count($scripts)) {
            return 0;
        }

        $io = $this->getIO();
        $io->writeError('<info>scripts:</info>');
        $rows = [];
        foreach ($scripts as $name => $list) {
            $description = '["' . implode('", "', $list) . '"]';
            $rows[] = ['  ' . $name, $description];
        }

        $table = $utils->table();
        $table->setRows($rows)->render();

        return 0;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
    }
}
