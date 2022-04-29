<?php

namespace SteadyUa\Unicorn\Cmd;

use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Throwable;

abstract class AbstractCmd
{
    /** @var AbstractCmd|null  */
    protected $next = null;

    public function exec(IOInterface $io): void
    {
        if ($this->next) {
            $this->next->exec($io);
        }
    }

    public function undo(IOInterface $io): void
    {
        if ($this->next) {
            $this->next->undo($io);
        }
    }

    public static function emptyCmd(): AbstractCmd
    {
        return new class extends AbstractCmd {
        };
    }

    public static function runCmd(AbstractCmd $cmd, IOInterface $io): int
    {
        try {
            $cmd->exec($io);
        } catch (Throwable $ex) {
            $io->write('<error> ' . $ex->getMessage() . ' </error> undo changes');
            try {
                $cmd->undo($io);
            } catch (Throwable $ex) {
                $io->write('<error> ' . $ex->getMessage() . ' </error> stopped');
            }

            return false;
        }

        return true;
    }

    public function setNext(AbstractCmd $next): AbstractCmd
    {
        $this->next = $next;

        return $next;
    }

    protected function packageFile(PackageInterface $package): string
    {
        return $package->getDistUrl()  . '/composer.json';
    }

    protected function packageLockFile(PackageInterface $package): string
    {
        return $package->getDistUrl()  . '/composer.lock';
    }
}
