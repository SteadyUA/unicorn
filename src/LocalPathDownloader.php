<?php

namespace SteadyUa\Unicorn;

use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\Downloader\DownloaderInterface;
use Composer\IO\IOInterface;
use Composer\Package\Archiver\ArchivableFilesFinder;
use Composer\Package\PackageInterface;
use Composer\Util\Filesystem;
use Composer\Util\ProcessExecutor;
use React\Promise\PromiseInterface;
use Symfony\Component\Filesystem\Filesystem as SymfonyFilesystem;

use function React\Promise\resolve;

class LocalPathDownloader implements DownloaderInterface
{
    private DownloaderInterface $sourceDownloader;
    private IOInterface $io;
    private Filesystem $filesystem;

    public function __construct(DownloaderInterface $sourceDownloader, IOInterface $io)
    {
        $this->sourceDownloader = $sourceDownloader;
        $this->io = $io;
        $this->filesystem = new Filesystem(new ProcessExecutor($io));
    }

    public function remove(PackageInterface $package, $path, $output = true): PromiseInterface
    {
        if ($output) {
            $this->io->writeError('  - ' . UninstallOperation::format($package));
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

    public function prepare(
        $type,
        PackageInterface $package,
        $path,
        PackageInterface $prevPackage = null
    ): PromiseInterface {
        return $this->sourceDownloader->prepare($type, $package, $path, $prevPackage);
    }

    /**
     * @inheritDoc
     */
    public function install(PackageInterface $package, string $path, bool $output = true): PromiseInterface
    {
        if ($package->getTransportOptions()['symlink'] ?? true) {
            return $this->sourceDownloader->install($package, $path, $output);
        }

        $path = Filesystem::trimTrailingSlash($path);
        $url = $package->getDistUrl();
        $realUrl = realpath($url);
        if (realpath($path) === $realUrl) {
            if ($output) {
                $this->io->writeError("  - " . InstallOperation::format($package) . ': Source already present');
            }

            return resolve(null);
        }

        $symfonyFilesystem = new SymfonyFilesystem();
        $this->filesystem->removeDirectory($path);
        if ($output) {
            $this->io->writeError("  - " . InstallOperation::format($package) . ': ', false);
        }

        $realUrl = $this->filesystem->normalizePath($realUrl);
        $iterator = new ArchivableFilesFinder(
            $realUrl,
            ['vendor', 'composer.lock']
        );
        $symfonyFilesystem->mirror($realUrl, $path, $iterator);

        if ($output) {
            $this->io->writeError(sprintf('%sMirroring from %s', '', $url));
        }

        return resolve(null);
    }

    public function update(PackageInterface $initial, PackageInterface $target, $path): PromiseInterface
    {
        return $this->sourceDownloader->update($initial, $target, $path);
    }

    public function cleanup(
        $type,
        PackageInterface $package,
        $path,
        PackageInterface $prevPackage = null
    ): PromiseInterface {
        return $this->sourceDownloader->cleanup($type, $package, $path, $prevPackage);
    }
}
