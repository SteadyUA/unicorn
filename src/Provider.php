<?php

namespace SteadyUa\Unicorn;

use Composer\Composer;
use Composer\DependencyResolver\Request;
use Composer\Factory;
use Composer\Filter\PlatformRequirementFilter\PlatformRequirementFilterFactory;
use Composer\Installer;
use Composer\IO\BufferIO;
use Composer\IO\IOInterface;
use Composer\IO\NullIO;
use Composer\Json\JsonFile;
use Composer\Package\Loader\ArrayLoader;
use Composer\Package\PackageInterface;
use Composer\Package\Version\VersionParser;
use Composer\Repository\CompositeRepository;
use Composer\Repository\InstalledRepository;
use Composer\Semver\Constraint\Constraint;
use Composer\Util\Platform;
use Symfony\Component\Console\Output\OutputInterface;

class Provider
{
    private array $config = [];
    private PathUtil $pathUtil;
    private ?Composer $uniComposer;
    private Composer $composer;

    public function __construct(string $cwd, Composer $composer)
    {
        $this->composer = $composer;
        $this->pathUtil = new PathUtil($cwd);
        $unicornJsonFile = $this->findUnicornJsonFile($cwd);
        if ($unicornJsonFile) {
            $this->config = (new JsonFile($unicornJsonFile))->read();
            $this->config['path'] = $unicornJsonFile;
        }
    }

    private function findUnicornJsonFile(string $cwd): ?string
    {
        $path = Platform::getEnv('UNI_PATH');
        if ($path && file_exists($path)) {
            return $path;
        }
        $path = $cwd;
        $home = realpath(getenv('HOME') ?: getenv('USERPROFILE') ?: '/');
        while (dirname($path) !== $path && $path != $home) {
            $unicornJsonFile = $path . '/unicorn.json';
            if (file_exists($unicornJsonFile)) {
                return $unicornJsonFile;
            }
            $path = dirname($path);
        }

        return null;
    }

    public function getDir(): string
    {
        return dirname($this->config['path']);
    }

    private function injectUniRepo()
    {
        if (empty($this->config)) {
            return;
        }

        $uniRepoCfg = [
            'type' => 'path',
            'url' => $this->relative($this->getDir() . '/uni_vendor/*/*'),
            'options' => ['versions' => []],
        ];

        $installedInfo = (new JsonFile($this->getDir() . '/uni_vendor/composer/installed.json'))->read();
        foreach ($installedInfo['packages'] as $package) {
            $uniRepoCfg['options']['versions'][$package['name']] = $package['version'];
        }
        if (Platform::getEnv('UNI_BUILD')) {
            $uniRepoCfg['options']['symlink'] = false;
        }

        $rm = $this->composer->getRepositoryManager();
        $rm->setRepositoryClass('path', LocalPathRepository::class);
        $rm->prependRepository(
            $rm->createRepository($uniRepoCfg['type'], $uniRepoCfg)
        );
    }

    private function changeDir(string $dir): string
    {
        $cwd = getcwd();
        $this->pathUtil = new PathUtil($dir);
        chdir($dir);

        return $cwd;
    }

    public function setupUniComposer(IOInterface $io): void
    {
        $currentPackageName = $this->composer->getPackage()->getName();
        $isRoot = $currentPackageName == '__root__';
        if (!$isRoot && !$this->localRepo()->findPackage($currentPackageName, '*')) {
            return;
        }

        if (!isset($_SERVER['backup_composer']) && file_exists('composer.json')) {
            $_SERVER['backup_composer'] = file_get_contents('composer.json');
        }

        $this->uniComposer = null;
        $cwd = $this->changeDir($uniDir = $this->getDir());
        if ($io->isVerbose()) {
            $io->write(" \xf0\x9f\xa6\x84 <info> initialization: $uniDir</info>");
        }
        $verbose = $io->isVerbose()
            ? OutputInterface::VERBOSITY_VERBOSE
            : ($io->isVeryVerbose() ? OutputInterface::VERBOSITY_VERY_VERBOSE : OutputInterface::VERBOSITY_NORMAL);
        $uniIo = new BufferIO('', $verbose);
        $uniComposer = $this->uniComposer($uniIo);

        $isLocked = $uniComposer->getLocker()->isLocked();
        $isFresh = $uniComposer->getLocker()->isFresh();
        $vendorExists = file_exists($uniComposer->getConfig()->get('vendor-dir'));

        if (!$isLocked || !$isFresh || !$vendorExists) {

            // refresh
            $install = Installer::create($uniIo, $uniComposer);
            $install
                ->disablePlugins()
                ->setDevMode()
                ->setDumpAutoloader()
                ->setPlatformRequirementFilter(PlatformRequirementFilterFactory::ignoreNothing())
            ;

            if (!$isLocked) {
                $install->setUpdate(true);
            } elseif (!$isFresh && $vendorExists) {
                $lockedRepo = $uniComposer->getLocker()->getLockedRepository();
                $localRepo = $this->localRepo();
                $updateList = [];
                foreach ($localRepo->getPackages() as $package) {
                    $lockedPackage = $lockedRepo->findPackage($package->getName(), '*');
                    if (!isset($lockedPackage)
                        || $lockedPackage->getDistReference() !== $package->getDistReference()
                    ) {
                        $updateList[] = $package->getName();
                    }
                    foreach ($package->getRequires() as $reqName => $reqLink) {
                        if (str_contains($reqName, '/')) {
                            $lockedPackage = $lockedRepo->findPackage($reqName, '*');
                            if ($lockedPackage) {
                                $locked = new Constraint('=', $lockedPackage->getVersion());
                                if (!$reqLink->getConstraint()->matches($locked)) {
                                    $updateList[] = $reqName;
                                }
                            }
                        }
                    }
                }
                if ($updateList) {
                    $install
                        ->setUpdate(true)
                        ->setUpdateAllowList($updateList)
                        ->setUpdateAllowTransitiveDependencies(Request::UPDATE_ONLY_LISTED)
                    ;
                } else {
                    $uniComposer->getLocker()->writeHash();
                }
            }

            if ($install->run()) {
                $io->writeError(" \xf0\x9f\xa6\x84 <error> Dependency error </error>");
                $io->writeError($uniIo->getOutput());
                $this->changeDir($cwd);
                if (isset($_SERVER['backup_composer'])
                    && file_get_contents('composer.json') !== $_SERVER['backup_composer']
                ) {
                    $io->writeError('<error> Reverting composer.json original content. </error>');
                    file_put_contents('composer.json', $_SERVER['backup_composer']);
                }
                exit(1);
            } elseif ($io->isVerbose()) {
                $output = array_filter(
                    preg_split("/[\n\r]+/", $uniIo->getOutput()),
                    function ($line) {
                        if (preg_match('/Dependency .+ root dependencies/', $line)
                            || preg_match('/Package .+ is abandoned/', $line)
                            || strpos($line, 'looking for funding')
                            || strpos($line, 'composer fund')
                        ) {
                            return false;
                        }
                        return true;
                    }
                );
                $io->writeError($output);
            }
            if (!$vendorExists) {
                $uniComposer->getLocker()->writeHash();
            }
        } elseif ($io->isVerbose()) {
            $io->write('    <info> Nothing to install, update or remove </info>');
        }

        $this->changeDir($cwd);

        if (!$isRoot) {
            $dm = $this->composer->getDownloadManager();
            $dm->setDownloader('path', new LocalPathDownloader($dm->getDownloader('path'), $io));
            $this->injectUniRepo();
        }
    }

    public function uniComposer(IOInterface $io = null): Composer
    {
        if (isset($this->uniComposer)) {
            return $this->uniComposer;
        }

        $io = $io ?? new NullIO();

        $unicornDir = $this->getDir();
        $uniConfig = [
            'name' => 'local/unicorn',
            'type' => 'metapackage',
            'require' => [],
            'config' => [
                'vendor-dir' => $unicornDir . '/uni_vendor',
            ],
        ];
        $composer = (new Factory())->createComposer($io, $uniConfig, true, $unicornDir);

        // init repos
        $rm = $composer->getRepositoryManager();
        $rm->setRepositoryClass('path', UniLocalPathRepository::class);
        $requires = [];
        $references = [];
        foreach ($this->reposFromFile($this->config) as $cfg) {
            $rm->addRepository(
                $repo = $rm->createRepository($cfg['type'], $cfg)
            );
            if ($cfg['type'] == 'path') {
                foreach ($repo->getPackages() as $package) {
                    if (isset($package->getExtra()['uni_exclude'])) {
                        continue;
                    }
                    $requires[$package->getName()] = $package->getVersion();
                    $references[$package->getName()] = $package->getDistReference();
                }
            }
        }

        // init locker
        $lockFile = new JsonFile($unicornDir . '/unicorn.lock', null, $io);
        $locker = new UniLocker(
            $io,
            $lockFile,
            $composer->getInstallationManager(),
            '{"extra": { "options": {'
                . implode(
                    ', ',
                    array_map(
                        function ($key, $value) {
                            return '"' . $key . '": "' . $value . '"';
                        },
                        array_keys($references),
                        array_values($references)
                    )
                )
                . '}}}',
            $composer->getLoop()->getProcessExecutor()
        );
        $composer->setLocker($locker);
        $dm = $composer->getDownloadManager();
        $dm->setDownloader('path', new LocalPathDownloader($dm->getDownloader('path'), $io));

        // inject requires
        $uniPackage = $composer->getPackage();
        $links = (new ArrayLoader())->parseLinks(
            $uniPackage->getName(),
            $uniPackage->getPrettyVersion(),
            'requires',
            $requires
        );
        $uniPackage->setRequires($links);

        return $this->uniComposer = $composer;
    }

    public function localRepo(): CompositeRepository
    {
        static $repo;
        if (isset($repo)) {
            return $repo;
        }

        $rm = $this->composer->getRepositoryManager();
        $repo = new CompositeRepository([]);

        foreach ($this->reposFromFile($this->config) as $cfg) {
            if ($cfg['type'] == 'path') {
                $pathRepo = $rm->createRepository($cfg['type'], $cfg);
                foreach ($pathRepo->getPackages() as $package) {
                    if (isset($package->getExtra()['uni_exclude'])) {
                        $pathRepo->removePackage($package);
                    }
                }
                $repo->addRepository($pathRepo);
            }
        }

        return $repo;
    }

    private function reposFromFile(array $pkgInfo): array
    {
        if (!isset($pkgInfo['repositories'])) {
            return [];
        }
        $repoList = [];
        $dirPath = $dirPath ?? dirname($pkgInfo['path']);
        $pathUtil = new PathUtil($dirPath);
        foreach ($pkgInfo['repositories'] as $repo) {
            $repo['options']['reference'] = 'config';
            if ($repo['type'] == 'path' && PathUtil::isRelative($repo['url'])) {
                $repo['url'] = $this->relative($pathUtil->absolute($repo['url']));
            }
            $repoList[] = $repo;
        }

        return array_reverse($repoList);
    }

    public function relative(string $url, bool $fromRoot = false): string
    {
        if ($fromRoot) {
            $url = realpath($this->getDir() . '/' . $url);
        }
        $path = $url;
        $pos = strpos($url, '/*');
        if ($pos) {
            $path = substr($url, 0, $pos);
        }
        $path = $this->pathUtil->relative($path);
        if (substr($path, 0, 1) !== '.') {
            $path = './' . $path;
        }
        if ($pos) {
            $path .= substr($url, $pos);
        }

        return $path;
    }

    public function isActive(): bool
    {
        return !empty($this->config);
    }

    public function getBuildInstallOptions(): string
    {
        return $this->config['extra']['build-install-options'] ?? '';
    }

    public function getPostUpdateScripts(): array
    {
        $scripts = $this->config['extra']['post-update-scripts'] ?? [];
        if (is_string($scripts)) {
            return [$scripts];
        }

        return $scripts;
    }

    public function composer(): Composer
    {
        return $this->composer;
    }

    private function processDepsResults(array $results): array
    {
        $depends = [];
        while (!empty($results)) {
            $queue = [];
            foreach ($results as $result) {
                [$depPkg, , $children] = $result;
                if (!isset($depends[$depPkg->getName()])) {
                    $depends[$depPkg->getName()] = $this->localRepo()->findPackage($depPkg->getName(), '*');
                }
                if ($children) {
                    $queue = array_merge($queue, $children);
                }
            }
            $results = $queue;
        }

        return $depends;
    }

    /**
     * @param PackageInterface $package
     * @param string $constraint
     * @param bool $recursive
     * @return array<string, PackageInterface>
     */
    public function getProhibits(PackageInterface $package, string $constraint, bool $recursive = false): array
    {
        $installedRepo = new InstalledRepository(
            [$this->uniComposer()->getRepositoryManager()->getLocalRepository()]
        );
        $needle = $package->getName();
        $results = $installedRepo->getDependents(
            [$needle],
            (new VersionParser())->parseConstraints($constraint),
            true,
            $recursive
        );

        return $this->processDepsResults($results);
    }

    /**
     * @param PackageInterface $package
     * @param bool $recursive
     * @return array<string, PackageInterface>
     */
    public function getDepends(PackageInterface $package, bool $recursive = false): array
    {
        $installedRepo = new InstalledRepository(
            [$this->uniComposer()->getRepositoryManager()->getLocalRepository()]
        );
        $needle = $package->getName();
        $results = $installedRepo->getDependents(
            [$needle],
            null,
            false,
            $recursive
        );

        return $this->processDepsResults($results);
    }

    public function unicornJsonFile(): ?string
    {
        return $this->config['path'] ?? null;
    }
}
