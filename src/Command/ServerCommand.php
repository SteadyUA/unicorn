<?php

namespace SteadyUa\Unicorn\Command;

use Composer\Command\BaseCommand;
use SteadyUa\Unicorn\Provider;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ServerCommand extends BaseCommand
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
            ->setName('uni:server')
            ->setDescription('Run http server.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $rootDir = $this->provider->getDir();
        $packageName = $this->provider->composer()->getPackage()->getName();
        if ($packageName == '__root__') {
            unset($packageName);
        }

        $dir = dirname(__DIR__, 2);
        $port = 8067;
        $cmd = 'php -S 0.0.0.0:' . $port . ' ' . $dir . '/ui/index.php 2>/dev/null 1>/dev/null';
        // TODO check is already run.
        // TODO port as parameter
        $link = 'http://127.0.0.1:' . $port . '/?r=' . $rootDir . '&p=' . ($packageName ?? '_list');
        $output->writeln("<href=$link>$link</>");

        passthru($cmd);

        return self::SUCCESS;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
    }
}
