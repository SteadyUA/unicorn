<?php

namespace SteadyUa\Unicorn\Command;

use Composer\Command\BaseCommand;
use Composer\Util\ProcessExecutor;
use SteadyUa\Unicorn\Provider;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SplitCommand extends BaseCommand
{
    private Provider $provider;

    public function __construct(Provider $provider)
    {
        $this->provider = $provider;
        parent::__construct();
    }

    protected function configure(): void
    {
        parent::configure();
        $this->setName('uni:split');
        $this->setDescription('Splits monorepo packages into their own remote repositories.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = $this->getIO();
        $binaryPath = $this->ensureSplitshLite();
        $process = new ProcessExecutor($io);
        
        $splitConfig = $this->provider->getSplitConfig();
        $globalPattern = $splitConfig['remote-pattern'] ?? null;
        $globalBranch = $splitConfig['remote-branch'] ?? getenv('UNI_SPLIT_BRANCH') ?: 'main';

        $packages = $this->provider->localRepo()->getPackages();
        $hasErrors = false;

        foreach ($packages as $package) {
            $extra = $package->getExtra();
            $localSplitConfig = $extra['uni-split'] ?? [];
            
            if ($localSplitConfig === false) {
                if ($io->isVerbose()) {
                    $io->write("Skipping <info>{$package->getName()}</info>: explicitly disabled via 'uni-split': false.");
                }
                continue;
            }

            if (!is_array($localSplitConfig)) {
                $localSplitConfig = [];
            }

            $pattern = $localSplitConfig['remote-pattern'] ?? $globalPattern;

            if (!$pattern) {
                if ($io->isVerbose()) {
                    $io->write("Skipping <info>{$package->getName()}</info>: no 'remote-pattern' configured globally or locally.");
                }
                continue;
            }

            $shortName = explode('/', $package->getName())[1] ?? $package->getName();
            $splitUrl = str_replace('{name}', $shortName, $pattern);

            // Expand env variables
            $url = preg_replace_callback('/\$\{([^}]+)\}/', function ($m) {
                return getenv($m[1]) ?: '';
            }, $splitUrl);

            $prefix = ltrim($this->provider->relative($package->getDistUrl()), './');
            $io->write("<info>Splitting {$package->getName()} ($prefix)...</info>");

            $cmd = sprintf('%s --prefix=%s', escapeshellarg($binaryPath), escapeshellarg($prefix));
            $splitOutput = '';
            if (0 !== $process->execute($cmd, $splitOutput, $this->provider->getDir())) {
                $io->writeError("<error>Failed to split $prefix</error>");
                $io->writeError($process->getErrorOutput());
                $hasErrors = true;
                continue;
            }
            
            $sha = trim($splitOutput);
            if (strlen($sha) !== 40) {
                $io->writeError("<error>Invalid SHA returned for $prefix: $sha</error>");
                $hasErrors = true;
                continue;
            }

            $branch = $localSplitConfig['remote-branch'] ?? $globalBranch;
            $io->write("  <comment>Pushing $sha to $branch...</comment>");
            $pushCmd = sprintf(
                'git push %s %s:refs/heads/%s',
                escapeshellarg($url),
                escapeshellarg($sha),
                escapeshellarg($branch)
            );

            $pushOutput = '';
            if (0 !== $process->execute($pushCmd, $pushOutput, $this->provider->getDir())) {
                $io->writeError("<error>Failed to push {$package->getName()}</error>");
                $io->writeError($process->getErrorOutput());
                $hasErrors = true;
                continue;
            }

            $version = $package->getPrettyVersion();
            if ($version && !str_starts_with($version, 'dev-') && $version !== 'dev-main' && $version !== 'No version set (parsed as 1.0.0)') {
                $tag = str_starts_with($version, 'v') ? $version : 'v' . $version;
                if (isset($localSplitConfig['remote-tag-prefix']) || isset($globalSplitConfig['remote-tag-prefix'])) {
                    $prefix = $localSplitConfig['remote-tag-prefix'] ?? $globalSplitConfig['remote-tag-prefix'];
                    $tag = $prefix . ltrim($version, 'v');
                }
                $io->write("  <comment>Pushing tag $tag...</comment>");
                $tagCmd = sprintf(
                    'git push %s %s:refs/tags/%s',
                    escapeshellarg($url),
                    escapeshellarg($sha),
                    escapeshellarg($tag)
                );
                $exitCode = $process->execute($tagCmd, $pushOutput, $this->provider->getDir());
                if ($exitCode !== 0) {
                    $errorOutput = $process->getErrorOutput();
                    if (str_contains($errorOutput, 'already exists') || str_contains($errorOutput, 'rejected')) {
                        $io->write("    <comment>Tag $tag already exists on remote. Skipping.</comment>");
                    } else {
                        $io->writeError("<error>Failed to push tag $tag</error>");
                        $io->writeError($errorOutput);
                        $hasErrors = true;
                    }
                }
            }
            
            $io->write("  <info>Success!</info>");
        }

        return $hasErrors ? 1 : 0;
    }

    private function ensureSplitshLite(): string
    {
        $cacheDir = $this->provider->composer()->getConfig()->get('cache-dir') . '/uni';
        $binaryPath = $cacheDir . '/splitsh-lite';

        if (file_exists($binaryPath)) {
            return $binaryPath;
        }

        $os = php_uname('s');
        $arch = php_uname('m');
        $filename = '';

        if (stripos($os, 'linux') !== false) {
            $filename = (stripos($arch, 'aarch64') !== false || stripos($arch, 'arm64') !== false) 
                ? 'lite_linux_arm64.tar.gz' 
                : 'lite_linux_amd64.tar.gz';
        } elseif (stripos($os, 'darwin') !== false) {
            $filename = stripos($arch, 'arm64') !== false 
                ? 'lite_darwin_arm64.tar.gz' 
                : 'lite_darwin_amd64.tar.gz';
        } else {
            throw new \RuntimeException('OS not supported by splitsh-lite auto-downloader.');
        }

        $url = "https://github.com/splitsh/lite/releases/download/v1.0.1/" . $filename;
        $this->getIO()->write("<info>Downloading splitsh-lite from $url...</info>");

        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }

        $tgz = @file_get_contents($url);
        if (!$tgz) {
            throw new \RuntimeException("Failed to download splitsh-lite from $url");
        }

        $tgzPath = $cacheDir . '/splitsh-lite.tar.gz';
        file_put_contents($tgzPath, $tgz);

        $process = new ProcessExecutor($this->getIO());
        if (0 !== $process->execute('tar -xzf splitsh-lite.tar.gz', $output, $cacheDir)) {
            throw new \RuntimeException("Failed to extract splitsh-lite: " . $process->getErrorOutput());
        }

        unlink($tgzPath);
        chmod($binaryPath, 0755);

        return $binaryPath;
    }
}
