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
        $config = $this->provider->config();
        if (empty($config)) {
            $io->writeln('Source not found.');
            return self::FAILURE;
        }

        $data = [[$config['path']]];
        if ($input->getOption('plain')) {
            foreach ($data as $line) {
                echo "{$line[0]}\n";
            }
        } else {
            $io->table(['path'], $data);
        }

        return self::SUCCESS;
    }
}
