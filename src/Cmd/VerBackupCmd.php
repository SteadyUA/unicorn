<?php

namespace SteadyUa\Unicorn\Cmd;

use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;

class VerBackupCmd extends AbstractCmd
{
    private $backup;

    public function __construct(
        PackageInterface $package,
        array $blocked
    ) {
        $this->backup = new FileBackup();
        $this->backup->addFile($this->packageFile($package));
        foreach ($blocked as $blockedPackage) {
            $this->backup->addFile($this->packageFile($blockedPackage));
        }
    }

    public function exec(IOInterface $io): void
    {
        $this->backup->backup();
        parent::exec($io);
        $this->backup->clean();
    }

    public function undo(IOInterface $io): void
    {
        $this->backup->restore();
        parent::undo($io);
    }
}
