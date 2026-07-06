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

    protected function configure(): void
    {
        $this
            ->setName('uni:server')
            ->setDescription('Run http server.')
            ->setHelp('This command runs the built-in HTTP server. You can customize the port by setting the <info>UNI_SERVER_PORT</info> environment variable (default is 8067).')
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
        $portEnv = getenv('UNI_SERVER_PORT');
        $port = $portEnv !== false ? (int)$portEnv : 8067;

        if (PHP_OS_FAMILY === 'Windows') {
            $output = shell_exec("netstat -ano | findstr :{$port} 2>nul");
            if ($output) {
                foreach (explode("\n", trim($output)) as $line) {
                    if (preg_match('/LISTENING\s+(\d+)/i', $line, $matches)) {
                        $pid = $matches[1];
                        if ($pid) {
                            exec("taskkill /F /PID $pid 2>nul");
                        }
                    }
                }
            }
        } else {
            $pids = shell_exec("lsof -t -i:{$port} 2>/dev/null");
            if ($pids) {
                foreach (explode("\n", trim($pids)) as $pid) {
                    if ($pid) {
                        exec("kill -9 $pid 2>/dev/null");
                    }
                }
            }
        }

        $cmd = 'php -S 0.0.0.0:' . $port . ' ' . $dir . '/ui/index.php 2>/dev/null 1>/dev/null';
        $link = 'http://127.0.0.1:' . $port . '/?r=' . $rootDir . '&p=' . ($packageName ?? '_list');
        $output->writeln("<href=$link>$link</>");
        $output->writeln("Press <info>Ctrl+C</info> to stop the server.");

        passthru($cmd);

        return self::SUCCESS;
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
    }
}
