<?php

namespace SteadyUa\Unicorn\Action;

use Composer\IO\IOInterface;

class ReqBackupAction extends AbstractAction
{
    private FileBackup $backup;

    public function __construct(
        array $changes
    ) {
        $this->backup = new FileBackup();
        foreach ($changes as $change) {
            $this->backup->addFile($this->packageFile($change['pkg']));
            $this->backup->addFile($this->packageLockFile($change['pkg']));
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
