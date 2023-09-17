<?php

namespace SteadyUa\Unicorn\Action;

use Composer\IO\IOInterface;
use RuntimeException;

class ReqTryInstallAction extends AbstractAction
{
    private array $packageName;

    public function __construct(array $packageName)
    {
        $this->packageName = $packageName;
    }

    public function exec(IOInterface $io): void
    {
        $io->write('<info>composer uni:install</info>');
        exec('composer uni:install -v ' . implode(' ', $this->packageName) . ' 2>&1', $output, $return);
        if ($return) {
            $io->write($output);
            throw new RuntimeException('composer uni:install failed');
        }

        parent::exec($io);
    }

    public function undo(IOInterface $io): void
    {
        $io->write('<info>undo composer uni:install</info>');
        exec('composer uni:install 2>&1', $output, $return);
        if ($return) {
            $io->write($output);
            throw new RuntimeException('undo composer uni:install failed');
        }

        parent::undo($io);
    }
}
