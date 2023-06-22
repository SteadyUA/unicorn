<?php

namespace SteadyUa\Unicorn\Action;

use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;

class ReqUpdateFilesAction extends AbstractAction
{
    /** @var array<string, array> */
    private array $changes;

    public function __construct(
        array $changes
    ) {
        $this->changes = $changes;
    }

    public function exec(IOInterface $io): void
    {
        $names = [];
        foreach ($this->changes as $name => $change) {
            $names[] = $name;
        }
        $io->write('<info>updating packages:</info> ' . implode(' ', $names));

        /** @var array{pkg: PackageInterface, req: array<string, string>} $change */
        foreach ($this->changes as $change) {
            $lockFile = $this->packageLockFile($change['pkg']);
            if (file_exists($lockFile)) {
                unlink($lockFile);
            }
            $composerJsonPath = $this->packageFile($change['pkg']);
            $content = file_get_contents($composerJsonPath);
            foreach ($change['req'] as $package => $constraint) {
                $content = preg_replace(
                    '#("' . preg_quote($package). '":\s*)"[^"]+"#',
                    '\\1"' . $constraint . '"',
                    $content
                );
            }
            file_put_contents($composerJsonPath, $content);
        }

        parent::exec($io);
    }
}
