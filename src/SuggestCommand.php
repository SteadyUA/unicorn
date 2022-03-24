<?php

namespace SteadyUa\Unicorn;

use Composer\Command\BaseCommand;
use Composer\Json\JsonFile;
use Composer\Repository\PathRepository;
use InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SuggestCommand extends BaseCommand
{
    public const SUCCESS = 0;
    public const FAILURE = 1;

    protected function configure()
    {
        $this
            ->setName('uni:suggest')
            ->setDescription('Suggest package by namespace.')
            ->addArgument('ns', InputArgument::IS_ARRAY|InputArgument::REQUIRED, 'namespace for search.')
            ->addOption('plain', null, InputOption::VALUE_NONE, 'Simple output.')
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
                } else {
                    if (substr($symbolName, 0, $nsLen) == $nsLower) {
                        return $ns;
                    }
                }
            }
        }

        return false;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $toFind = $input->getArgument('ns');
        $searchData = [];
        foreach ($toFind as $ns) {
            $searchData[strtolower($ns)] = strlen($ns);
        }

        $data = [];
        $possibleLockPath = [
            '.'
        ];
        foreach ($this->getComposer()->getRepositoryManager()->getRepositories() as $repository) {
            //Composer\Repository\FilterRepository
            if ($repository instanceof PathRepository) {
                foreach ($repository->getPackages() as $package) {
                    if (in_array($package->getType(), ['project', 'ph-project'])) {
                        $possibleLockPath[] = $package->getDistUrl();
                    }
                    if ($ns = $this->match($package->getAutoload(), $searchData)) {
                        $data += [$ns => [$package->getName(), $ns, $package->getDistUrl()]];
                    }
                }
            }
        }

        // external and soft deps
        foreach ($possibleLockPath as $path) {
            $lock = new JsonFile("$path/composer.lock");
            if (!$lock->exists()) {
                continue;
            }
            $nfo = $lock->read();
            foreach ($nfo['packages'] as $pkgInfo) {
                $autoload = $pkgInfo['autoload'] ?? null;
                if (isset($autoload)) {
                    if ($ns = $this->match($pkgInfo['autoload'], $searchData)) {
                        $data += [$ns => [$pkgInfo['name'], $ns, $pkgInfo['source']['url'] ?? $pkgInfo['dist']['url'] ?? 'unknown']];
                    }
                }
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
        if ($input->getOption('plain')) {
            foreach ($data as $line) {
                echo "{$line[0]}\t{$line[1]}\t{$line[2]}\n";
            }
        } else {
            $io->table(['name', 'namespace', 'path'], $data);
        }

        return self::SUCCESS;
    }
}
