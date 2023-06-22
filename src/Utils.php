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
            foreach ($scripts as $script) {
                $cmd = 'composer run ' . $script;
                $promises[] = $process->executeAsync($cmd, $pkgDir)->then(
                    function (Process $process) use (&$errors, $package, &$tasks, $script) {
                        if ($process->getExitCode() > 0) {
                            $tasks[$package->getName()][$script] = '<error>Failed</error>';
                            $errors[] = [
                                'pkg' => $package->getName(),
                                'cmd' => $process->getCommandLine(),
                                'dir' => $process->getWorkingDirectory(),
                                'out' => $process->getOutput(),
                                'err' => $process->getErrorOutput(),
                            ];
                        } else {
                            $tasks[$package->getName()][$script] = '<info>Ok</info>';
                        }
                    }
                );
            }
        }

        $this->executeCommands($promises, $process);

        $this->printReport($depends, $runScripts, $tasks);

        foreach ($errors as $error) {
            $this->io->write('<error> ' . $error['pkg'] . ' </error>');
            $this->io->write('<info> cmd: ' . $error['cmd'] . ' </info>');
            $this->io->write('<info> dir: ' . $error['dir'] . ' </info>');
            if ($error['out']) {
                $this->io->write($error['out']);
            }
            if ($error['err']) {
                $this->io->write($error['err']);
            }
        }

        return !empty($errors) ? 1 : 0;
    }

    public function install(array $packages, string $options = ''): int
    {
        $promises = [];
        $process = new ProcessExecutor($this->io);
        $process->enableAsync();
        $errors = [];
        foreach ($packages as $package) {
            $pkgDir = $package->getDistUrl();
            $cmd = 'rm -rf vendor composer.lock';
            $cmd .= ' && composer install -n ' . $options;
            $promises[] = $process->executeAsync($cmd, $pkgDir)->then(
                function (Process $process) use (&$errors, $package) {
                    if ($process->getExitCode() > 0) {
                        $errors[] = [
                            'pkg' => $package,
                            'cmd' => $process->getCommandLine(),
                            'out' => $process->getOutput(),
                            'err' => $process->getErrorOutput(),
                        ];
                    }
                }
            );
        }

        $this->executeCommands($promises, $process);

        foreach ($errors as $error) {
            $this->io->write('<error> ' . $error['pkg']->getName() . ' </error>');
            $this->io->write('<info> cmd: ' . $error['cmd'] . ' </info>');
            $this->io->write('<info> dir: ' . $error['pkg']->getDistUrl() . ' </info>');
            if ($error['out']) {
                $this->io->write($error['out']);
            }
            if ($error['err']) {
                $this->io->write($error['err']);
            }
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
