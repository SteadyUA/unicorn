<?php

namespace SteadyUa\Unicorn\Cmd;

use Composer\IO\IOInterface;
use RuntimeException;

class ReqTryInstallCmd extends AbstractCmd
{
    public function exec(IOInterface $io): void
    {
        $io->write('<info>composer uni:install</info>');
        exec('composer uni:install -v 2>&1', $output, $return);
        $io->write($this->filter($output));
        if ($return) {
            throw new RuntimeException('composer uni:install failed');
        }

        parent::exec($io);
    }

    public function undo(IOInterface $io): void
    {
        $io->write('<info>undo composer uni:install</info>');
        exec('composer uni:install 2>&1', $output, $return);
        if ($return) {
            $io->write($this->filter($output));
            throw new RuntimeException('undo composer uni:install failed');
        }

        parent::undo($io);
    }

    private function filter(array $output): array
    {
        $filtered = [];
        foreach ($output as $line) {
            if (!preg_match('/^Dependency.+root dependencies/', $line)
                && !preg_match('/^Package .+ is abandoned/', $line)
                && !strpos($line, 'looking for funding')
                && !strpos($line, 'composer fund')
            ) {
                $filtered[] = $line;
            }
        }

        return $filtered;
    }
}
