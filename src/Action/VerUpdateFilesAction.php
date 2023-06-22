<?php

namespace SteadyUa\Unicorn\Action;

use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use SteadyUa\Unicorn\Version;

class VerUpdateFilesAction extends AbstractAction
{
    private PackageInterface $package;
    /** @var PackageInterface[] */
    private array $blocked;
    private Version $newVersion;

    public function __construct(
        PackageInterface $package,
        array $blocked,
        Version $newVersion
    ) {
        $this->package = $package;
        $this->blocked = $blocked;
        $this->newVersion = $newVersion;
    }

    public function exec(IOInterface $io): void
    {
        $names = [$this->package->getName()];
        foreach ($this->blocked as $blockedPackage) {
            $names[] = $blockedPackage->getName();
        }
        $io->write('<info>updating packages:</info> ' . implode(' ', $names));
        $composerJsonPath = $this->packageFile($this->package);
        $fileContent = file_get_contents($composerJsonPath);
        $fileContent = preg_replace(
            '/("version":\s*)"[^"]+"/',
            '\\1"' . $this->newVersion . '"',
            $fileContent
        );
        file_put_contents($composerJsonPath, $fileContent);

        foreach ($this->blocked as $blockedPackage) {
            $composerJsonPath = $this->packageFile($blockedPackage);
            $content = file_get_contents($composerJsonPath);
            $content = preg_replace(
                '#("' . preg_quote($this->package->getName()). '":\s*)"[^"]+"#',
                '\\1"' . $this->newVersion->minorConstraint() . '"',
                $content
            );
            file_put_contents($composerJsonPath, $content);
        }

        parent::exec($io);
    }
}
