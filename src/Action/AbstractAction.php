<?php

namespace SteadyUa\Unicorn\Action;

use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Throwable;

abstract class AbstractAction
{
    protected ?AbstractAction $next = null;

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

    public static function emptyCmd(): AbstractAction
    {
        return new class extends AbstractAction {
        };
    }

    public static function runCmd(AbstractAction $cmd, IOInterface $io): int
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

    public function setNext(AbstractAction $next): AbstractAction
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
