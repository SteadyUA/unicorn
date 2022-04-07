<?php

namespace SteadyUa\Unicorn;

use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\Downloader\DownloaderInterface;
use Composer\Downloader\PathDownloader;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Util\Filesystem;
use Composer\Util\ProcessExecutor;
use React\Promise\PromiseInterface;

class LocalPathDownloader implements DownloaderInterface
{
    private $sourceDownloader;
    private $io;
    private $filesystem;

    public function __construct(PathDownloader $sourceDownloader, IOInterface $io)
    {
        $this->sourceDownloader = $sourceDownloader;
        $this->io = $io;
        $this->filesystem = new Filesystem( new ProcessExecutor($io));
    }

    public function remove(PackageInterface $package, $path, $output = true): PromiseInterface
    {
        if ($output) {
            $this->io->writeError("  - " . UninstallOperation::format($package));
        }
        $promise = $this->filesystem->removeDirectoryAsync($path);

        return $promise->then(function ($result) use ($path): void {
            if (!$result) {
                throw new \RuntimeException('Could not completely delete '.$path.', aborting.');
            }
        });
    }

    public function getInstallationSource(): string
    {
        return $this->sourceDownloader->getInstallationSource();
    }

    public function download(PackageInterface $package, $path, PackageInterface $prevPackage = null): PromiseInterface
    {
        return $this->sourceDownloader->download($package, $path, $prevPackage);
    }

    public function prepare($type, PackageInterface $package, $path, PackageInterface $prevPackage = null): PromiseInterface
    {
        return $this->sourceDownloader->prepare($type, $package, $path, $prevPackage);
    }

    public function install(PackageInterface $package, $path): PromiseInterface
    {
        return $this->sourceDownloader->install($package, $path);
    }

    public function update(PackageInterface $initial, PackageInterface $target, $path): PromiseInterface
    {
        return $this->sourceDownloader->update($initial, $target, $path);
    }

    public function cleanup($type, PackageInterface $package, $path, PackageInterface $prevPackage = null): PromiseInterface
    {
        return $this->sourceDownloader->cleanup($type, $package, $path, $prevPackage);
    }
}
