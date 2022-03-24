<?php

namespace SteadyUa\Unicorn;

use Composer\Command\BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SourceCommand extends BaseCommand
{
    /** @var Provider */
    private $provider;

    public const SUCCESS = 0;
    public const FAILURE = 1;

    public function __construct(Provider $provider)
    {
        $this->provider = $provider;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('uni:source')
            ->setDescription('Show list of repository sources.')
            ->addOption('plain', null, InputOption::VALUE_NONE, 'Simple output.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $repoList = $this->provider->repoList();
        if (empty($repoList)) {
            $io->writeln('Source not found.');
            return self::FAILURE;
        }

        $data = [];
        foreach ($repoList as $path => $pkgInfo) {
            $data[] = [$path, $pkgInfo['name'] ?? 'not set'];
        }
        if ($input->getOption('plain')) {
            foreach ($data as $line) {
                echo "{$line[0]}\t{$line[1]}\n";
            }
        } else {
            $io->table(['path', 'name'], $data);
        }

        return self::SUCCESS;
    }
}
