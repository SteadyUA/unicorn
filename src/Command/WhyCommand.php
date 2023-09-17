<?php

namespace SteadyUa\Unicorn\Command;

use Composer\Command\BaseDependencyCommand;
use Composer\Composer;
use SteadyUa\Unicorn\Provider;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class WhyCommand extends BaseDependencyCommand
{
    private Provider $provider;

    public function __construct(Provider $provider)
    {
        $this->provider = $provider;
        parent::__construct();
    }

    protected function configure()
    {
        parent::configure();
        $this
            ->setName('uni:why')
            ->setDescription('Shows which packages cause the given package to be installed.')
            ->setDefinition([
                new InputArgument(self::ARGUMENT_PACKAGE, InputArgument::OPTIONAL, 'Package to inspect'),
                new InputOption(
                    self::OPTION_RECURSIVE,
                    'r',
                    InputOption::VALUE_NONE,
                    'Recursively resolves up to the root package'
                ),
                new InputOption(self::OPTION_TREE, 't', InputOption::VALUE_NONE, 'Prints the results as a nested tree'),
                new InputOption('locked', null, InputOption::VALUE_NONE, 'Read dependency information from composer.lock'),
            ])
        ;
    }

    public function requireComposer(bool $disablePlugins = null, bool $disableScripts = null): Composer
    {
        return $this->provider->uniComposer();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$input->getArgument(self::ARGUMENT_PACKAGE)) {
            $package = $this->provider->composer()->getPackage()->getName();
            if ($package == '__root__') {
                $output->writeln('Could not find a composer.json file in ' . getcwd());
                return self::FAILURE;
            }
            $input->setArgument(self::ARGUMENT_PACKAGE, $package);
        }
        return parent::doExecute($input, $output);
    }

    protected function filterRoot($results): array
    {
        $filtered = [];
        foreach ($results as $result) {
            if (!empty($result[2])) {
                $result[2] = $this->filterRoot($result[2]);
            }
            if ($result[0]->getName() != 'local/unicorn') {
                $filtered[] = $result;
            }
        }
        return $filtered;
    }

    protected function printTable(OutputInterface $output, $results): void
    {
        parent::printTable($output, $this->filterRoot($results));
    }

    protected function printTree(array $results, string $prefix = '', int $level = 1): void
    {
        parent::printTree($this->filterRoot($results), $prefix, $level);
    }
}
