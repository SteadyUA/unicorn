<?php

namespace SteadyUa\Unicorn;

use Composer\Installer\InstallationManager;
use Composer\IO\IOInterface;
use Composer\Json\JsonFile;
use Composer\Package\Locker;
use Composer\Util\ProcessExecutor;

class UniLocker extends Locker
{
    private $contentHash;
    private $lockContentHash = '';
    private $contentHashFile;

    public function __construct(
        IOInterface $io,
        JsonFile $lockFile,
        InstallationManager $installationManager,
        string $composerFileContents,
        ProcessExecutor $process = null
    ) {
        parent::__construct($io, $lockFile, $installationManager, $composerFileContents, $process);
        $this->contentHash = self::getContentHash($composerFileContents);
        $this->contentHashFile = dirname($lockFile->getPath()) . '/uni_vendor/content.hash';
        if (file_exists($this->contentHashFile)) {
            $this->lockContentHash = file_get_contents($this->contentHashFile);
        }
    }

    public function isFresh(): bool
    {
        return $this->contentHash == $this->lockContentHash;
    }

    public function setLockData(
        array $packages,
        ?array $devPackages,
        array $platformReqs,
        array $platformDevReqs,
        array $aliases,
        string $minimumStability,
        array $stabilityFlags,
        bool $preferStable,
        bool $preferLowest,
        array $platformOverrides,
        bool $write = true
    ): bool {
        $uniDir = dirname($this->contentHashFile);
        if (!file_exists($uniDir)) {
            mkdir($uniDir);
        }
        file_put_contents($this->contentHashFile, $this->contentHash);
        $lockerClass = new \ReflectionClass(Locker::class);
        $contentHashProperty = $lockerClass->getProperty('contentHash');
        $contentHashProperty->setAccessible(true);
        $contentHashProperty->setValue($this, './uni_vendor/content.hash');

        return parent::setLockData(
            $packages,
            $devPackages,
            $platformReqs,
            $platformDevReqs,
            $aliases,
            $minimumStability,
            $stabilityFlags,
            $preferStable,
            $preferLowest,
            $platformOverrides,
            $write
        );
    }
}
