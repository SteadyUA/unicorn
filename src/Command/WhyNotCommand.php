<?php

namespace SteadyUa\Unicorn\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class WhyNotCommand extends WhyCommand
{
    protected function configure()
    {
        $this
            ->setName('uni:why-not')
            ->setDescription('Shows which packages prevent the given package from being installed.')
            ->setDefinition([
                new InputArgument(self::ARGUMENT_PACKAGE, InputArgument::REQUIRED, 'Package to inspect'),
                new InputArgument(
                    self::ARGUMENT_CONSTRAINT,
                    InputArgument::REQUIRED,
                    'Version constraint, which version you expected to be installed'
                ),
                new InputOption(
                    self::OPTION_RECURSIVE,
                    'r',
                    InputOption::VALUE_NONE,
                    'Recursively resolves up to the root package'
                ),
                new InputOption(self::OPTION_TREE, 't', InputOption::VALUE_NONE, 'Prints the results as a nested tree'),
            ])
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        return parent::doExecute($input, $output, true);
    }
}
