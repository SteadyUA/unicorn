<?php

namespace SteadyUa\Unicorn;

use Composer\IO\IOInterface;
use Composer\Package\CompletePackageInterface;
use Composer\Util\ProcessExecutor;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

use function React\Promise\all;

class Utils
{
    private IOInterface $io;
    private OutputInterface $output;

    public function __construct(IOInterface $io, OutputInterface $output)
    {
        $this->io = $io;
        $this->output = $output;
    }

    /**
     * @param array $runScripts
     * @param array<string, CompletePackageInterface> $depends
     * @return int
     */
    public function runScripts(array $runScripts, array $depends): int
    {
        $promises = [];
        $process = new ProcessExecutor($this->io);
        $process->enableAsync();
        $errors = [];
        $tasks = [];
        foreach ($depends as $pkgName => $package) {
            $scripts = $package->getScripts();
            foreach ($runScripts as $name) {
                if (isset($scripts[$name])) {
                    if (!isset($tasks[$pkgName])) {
                        $tasks[$pkgName] = [];
                    }
                    $tasks[$pkgName][$name] = $name;
                }
            }
        }
        if (empty($tasks)) {
            $this->printReport($depends, $runScripts, $tasks);
            return 0;
        }

        foreach ($tasks as $pkgName => $scripts) {
            $package = $depends[$pkgName];
            $pkgDir = $package->getDistUrl();
            $commands = [];
            foreach ($scripts as $script) {
                $cmd = 'composer run ' . $script;
                $commands[] = [
                    'pkg' => $package->getName(),
                    'script' => $script,
                    'cmd' => $cmd,
                    'dir' => $pkgDir,
                ];
                $tasks[$package->getName()][$script] = '<info>Ok</info>';
            }
        }
        $errors = $this->exec($commands, false);
        foreach ($errors as $error) {
            $tasks[$error['pkg']][$error['script']] = '<error>Failed</error>';
        }

        $this->printReport($depends, $runScripts, $tasks);

        foreach ($errors as $error) {
            $this->io->write('<error> ' . $error['pkg'] . ' </error>');
            $this->io->write('<info> cmd: ' . $error['cmd'] . ' </info>');
            $this->io->write('<info> dir: ' . $error['dir'] . ' </info>');
            $this->io->write($error['out']);
        }

        return !empty($errors) ? 1 : 0;
    }

    /** @param array{cmd:string,dir:string}[] $commands */
    private function exec(array $commands, bool $async = true): array
    {
        $process = new ProcessExecutor($this->io);
        $errors = [];
        if ($async == false) {
            $progress = $this->io->getProgressBar();
            $progress->start(count($commands));
            $i = 1;
            foreach ($commands as $command) {
                $output = '';
                $res = $process->execute($command['cmd'], $output, $command['dir']);
                if ($res > 0) {
                    $errors[] = $command + [
                        'out' => $output,
                    ];
                }
                $progress->setProgress($i ++);
            }
            $progress->clear();
        } else {
            $process->enableAsync();
            foreach ($commands as $command) {
                $promises[] = $process->executeAsync($command['cmd'], $command['dir'])->then(
                    function (Process $process) use (&$errors, $command) {
                        if ($process->getExitCode() > 0) {
                            $errors[] = $command + [
                                'out' => $process->getOutput() . $process->getErrorOutput(),
                            ];
                        }
                    }
                );
            }
            $this->executeCommands($promises, $process);
        }

        return $errors;
    }

    public function install(array $packages, string $options = '', bool $async = true): int
    {
        $commands = [];
        foreach ($packages as $package) {
            $pkgDir = realpath($package->getDistUrl());
            $cmd = 'rm -rf vendor composer.lock';
            $cmd .= ' && composer install -n ' . $options;
            $commands[] = [
                'pkg' => $package,
                'cmd' => $cmd,
                'dir' => $pkgDir,
            ];
        }
        $errors = $this->exec($commands, $async);

        foreach ($errors as $error) {
            $this->io->write('<error> ' . $error['pkg']->getName() . ' </error>');
            $this->io->write('<info> cmd: ' . $error['cmd'] . ' </info>');
            $this->io->write('<info> dir: ' . $error['pkg']->getDistUrl() . ' </info>');
            $this->io->write($error['out']);
        }

        return !empty($errors) ? 1 : 0;
    }

    private function printReport(array $depends, array $runScripts, array $tasks): void
    {
        $rows = [];
        $headers = ["Package"];
        foreach ($runScripts as $script) {
            $headers[] = $script;
        }
        foreach ($depends as $pkgName => $package) {
            $row = [$pkgName];
            foreach ($runScripts as $script) {
                $row[] = $tasks[$pkgName][$script] ?? 'No';
            }
            $rows[] = $row;
        }

        $table = $this->table();
        $table->setHeaders($headers)->setRows($rows)->render();
    }

    public function table(): Table
    {
        $table = new Table($this->output);
        $table->setStyle('compact');
        $style = $table->getStyle();
        $style->setCellRowContentFormat('%s  ');

        return $table;
    }

    private function executeCommands(array $promises, ProcessExecutor $process)
    {
        $uncaught = null;
        /** @var ProgressBar $progress */
        $progress = $this->io->getProgressBar();
        all($promises)->then(
            function (): void {
            },
            function ($e) use (&$uncaught): void {
                $uncaught = $e;
            }
        );

        $totalJobs = $process->countActiveJobs();
        $activeJobs = $totalJobs;
        $progress->start($totalJobs);

        $lastUpdate = 0;
        while ($activeJobs > 0) {
            if (microtime(true) - $lastUpdate > 0.1) {
                $lastUpdate = microtime(true);
                $progress->setProgress($progress->getMaxSteps() - $activeJobs);
            }
            $activeJobs = $process->countActiveJobs();
        }
        $progress->clear();

        if (isset($uncaught)) {
            throw $uncaught;
        }
    }
}
