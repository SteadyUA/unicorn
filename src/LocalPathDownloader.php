<?php

namespace SteadyUa\Unicorn;

use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\Downloader\DownloaderInterface;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Util\Filesystem;
use Composer\Util\ProcessExecutor;
use RuntimeException;

class LocalPathDownloader implements DownloaderInterface
{
    private $sourceDownloader;
    private $io;
    private $filesystem;

    public function __construct(DownloaderInterface $sourceDownloader, IOInterface $io)
    {
        $this->sourceDownloader = $sourceDownloader;
        $this->filesystem = new Filesystem(new ProcessExecutor($io));
        $this->io = $io;
    }

    public function remove(PackageInterface $package, $path, $output = true)
    {
        if ($output) {
            $this->io->writeError("  - " . UninstallOperation::format($package));
        }
        if (!$this->filesystem->removeDirectory($path)) {
            throw new RuntimeException('Could not completely delete ' . $path . ', aborting.');
        }
    }

    public function getInstallationSource()
    {
        return $this->sourceDownloader->getInstallationSource();
    }

    public function download(PackageInterface $package, $path, PackageInterface $prevPackage = null)
    {
        return $this->sourceDownloader->download($package, $path, $prevPackage);
    }

    public function prepare($type, PackageInterface $package, $path, PackageInterface $prevPackage = null)
    {
        return $this->sourceDownloader->prepare($type, $package, $path, $prevPackage);
    }

    public function install(PackageInterface $package, $path)
    {
        $this->sourceDownloader->install($package, $path);
    }

    public function update(PackageInterface $initial, PackageInterface $target, $path)
    {
        $this->sourceDownloader->update($initial, $target, $path);
    }

    public function cleanup($type, PackageInterface $package, $path, PackageInterface $prevPackage = null)
    {
        return $this->sourceDownloader->cleanup($type, $package, $path, $prevPackage);
    }
}
