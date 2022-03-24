<?php

namespace SteadyUa\Unicorn;

use Composer\Command\BaseCommand;
use Composer\Repository\PathRepository;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ProjectCommand extends BaseCommand
{
    public const SUCCESS = 0;
    public const FAILURE = 1;

    protected function configure()
    {
        $this
            ->setName('uni:project')
            ->setDescription('Show local projects.')
            ->addOption('plain', null, InputOption::VALUE_NONE, 'Simple output.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $data = [];
        foreach ($this->getComposer()->getRepositoryManager()->getRepositories() as $repository) {
            if ($repository instanceof PathRepository) {
                foreach ($repository->getPackages() as $package) {
                    if (substr($package->getType(), -7) == 'project') {
                        $data[] = [$package->getName(), $package->getDistUrl()];
                    }
                }
            }
        }

        usort($data, function ($left, $right) {
            return $left[0] <=> $right[0];
        });

        // show list
        if ($input->getOption('plain')) {
            foreach ($data as $line) {
                echo "{$line[0]}\t{$line[1]}\n";
            }
        } else {
            $io->table(['name', 'path'], $data);
        }

        return self::SUCCESS;
    }
}
