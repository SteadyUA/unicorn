<?php

namespace SteadyUa\Unicorn\Command;

use Composer\InstalledVersions;
use Composer\Json\JsonFile;
use Composer\Command\BaseCommand;
use Composer\Package\Loader\ArrayLoader;
use Composer\Package\Loader\ValidatingArrayLoader;
use Composer\Package\Version\VersionParser;
use SteadyUa\Unicorn\Provider;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use DirectoryIterator;
use Seld\JsonLint\ParsingException;

class DoctorCommand extends BaseCommand
{
    private Provider $provider;

    public function __construct(Provider $provider)
    {
        $this->provider = $provider;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('uni:doctor')
             ->setDescription('Diagnoses the state of the monorepo.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $version = InstalledVersions::getPrettyVersion('steady-ua/unicorn') ?? 'unknown';
        } catch (\OutOfBoundsException $e) {
            $version = 'source/dev';
        }
        $output->writeln("<info>Plugin Version:</info> $version");

        if (!$this->provider->isActive()) {
            $output->writeln("\n<error>Monorepo root not found.</error>");
            $output->writeln("Could not find a composer.json with type=monorepo in the current or parent directories.");
            return self::SUCCESS;
        }

        $root = $this->provider->getDir();
        $output->writeln("<info>Monorepo Root:</info> $root");

        $jsonPath = $this->provider->unicornJsonFile();
        $json = new JsonFile($jsonPath);
        try {
            $config = $json->read();
            $output->writeln("<info>Config:</info> monorepo root composer.json is valid.");
        } catch (\Exception $e) {
            $output->writeln("\n<error>monorepo root composer.json is invalid: " . $e->getMessage() . "</error>");
            return self::FAILURE;
        }

        $output->writeln("\n<info>Checking package directories...</info>");
        $repos = $config['repositories'] ?? [];
        $validPackages = 0;
        
        $baseDirs = [];
        $globs = [];

        // Collect all base directories and glob patterns
        foreach ($repos as $repo) {
            if (isset($repo['type']) && $repo['type'] === 'path' && isset($repo['url'])) {
                $url = $repo['url'];
                $absUrl = $root . '/' . ltrim($url, './');
                $globs[] = $absUrl;

                if (str_ends_with($url, '/*')) {
                    $baseDir = rtrim(substr($url, 0, -2), '/');
                    $absBaseDir = $root . '/' . ltrim($baseDir, './');
                    $baseDirs[$absBaseDir] = $url;
                } else {
                    $baseDir = dirname($url);
                    $absBaseDir = $root . '/' . ltrim($baseDir, './');
                    $baseDirs[$absBaseDir] = $url;
                }
            }
        }

        $notStable = $this->provider->getMinimumStability() !== 'stable';
        foreach ($baseDirs as $absBaseDir => $originalUrl) {
            if (!is_dir($absBaseDir)) {
                continue;
            }

            $iterator = new DirectoryIterator($absBaseDir);
            $pathPackageCount = 0;

            foreach ($iterator as $item) {
                if ($item->isDot() || !$item->isDir()) {
                    continue;
                }

                $itemPath = $item->getPathname();
                $relPath = ltrim(str_replace($root, '', $itemPath), '/');

                // Check if this directory matches ANY of the defined globs
                $matchesGlob = false;
                foreach ($globs as $glob) {
                    if (fnmatch($glob, $itemPath)) {
                        $matchesGlob = true;
                        break;
                    }
                }

                if ($matchesGlob) {
                    $composerJsonFile = $itemPath . '/composer.json';
                    if (file_exists($composerJsonFile)) {
                        try {
                            $jsonFile = new JsonFile($composerJsonFile);
                            $jsonData = $jsonFile->read();
                            
                            // Inject dummy version if missing, as path repos do this dynamically
                            if (!isset($jsonData['version']) && $notStable) {
                                $jsonData['version'] = '1.0.0';
                            }

                            $loader = new ValidatingArrayLoader(
                                new ArrayLoader(new VersionParser()),
                                true, // strictName
                                null,
                                ValidatingArrayLoader::CHECK_ALL
                            );
                            $loader->load($jsonData);

                            $pathPackageCount++;
                            $validPackages++;
                        } catch (ParsingException $e) {
                            $output->writeln("  <error>Invalid Package:</error> Directory <comment>./$relPath</comment> has an invalid composer.json: " . $e->getMessage());
                        } catch (\Exception $e) {
                            $output->writeln("  <error>Invalid Package:</error> Directory <comment>./$relPath</comment> failed validation:");
                            $errors = explode("\n", $e->getMessage());
                            foreach ($errors as $errorLine) {
                                $errorLine = trim($errorLine);
                                if ($errorLine === 'Invalid package information:' || $errorLine === '') {
                                    continue;
                                }
                                $output->writeln("    - " . $errorLine);
                            }
                        }
                    } else {
                        $output->writeln("  <error>Invalid Package:</error> Directory <comment>./$relPath</comment> matches glob but has no composer.json.");
                    }
                } else {
                    $output->writeln("  <error>Misplaced Directory:</error> Directory <comment>./$relPath</comment> does not match any package glob pattern.");
                }
            }
            $output->writeln("  Path <comment>$originalUrl</comment>: Found $pathPackageCount valid packages.");
        }

        $output->writeln("\n<info>Checking initialization...</info>");
        if (!file_exists($root . '/composer.lock') && !is_dir($root . '/vendor')) {
            $output->writeln("  <error>Not initialized.</error> Run <comment>uni:install</comment> to initialize the monorepo.");
            return self::SUCCESS;
        }
        $output->writeln("  Initialized properly.");

        $output->writeln("\n<info>Checking for orphaned dependencies...</info>");
        try {
            $localRepo = $this->provider->localRepo();
            $orphans = [];

            foreach ($localRepo->getPackages() as $package) {
                if ($package->getType() === 'project') {
                    continue;
                }

                $dependents = $this->provider->getDepends($package);
                if (empty($dependents)) {
                    $pathUtil = new \SteadyUa\Unicorn\PathUtil($root);
                    $absolutePath = realpath($package->getDistUrl());
                    $relPath = $absolutePath ? $pathUtil->relative($absolutePath) : $package->getDistUrl();
                    $orphans[] = [
                        'name' => $package->getName(),
                        'path' => $relPath
                    ];
                }
            }

            if (empty($orphans)) {
                $output->writeln("  No orphaned libraries found.");
            } else {
                foreach ($orphans as $orphan) {
                    $output->writeln("  <error>Orphan found:</error> Package <comment>{$orphan['name']}</comment> at <comment>./{$orphan['path']}</comment> is not required by any other local package.");
                }
            }
        } catch (\Throwable $e) {
            $output->writeln("  <error>Error loading local packages:</error> " . $e->getMessage());
            $output->writeln("  Cannot perform orphan dependency check until all composer.json files are valid.");
        }

        return self::SUCCESS;
    }
}
