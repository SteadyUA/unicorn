<?php

namespace SteadyUa\Unicorn\Server\LockReader;

class LockReader
{
    /** @var Package[] */
    private array $packages = [];
    private array $typeSet = [];

    /** @var Author[] */
    private array $author = [];
    /** @var Vendor[] */
    private array $vendors = [];

    private bool $showLocal;
    private bool $showExternal;
    private bool $showDev;

    public function __construct(string $lockFile, array $options)
    {
        $this->showLocal = $options['local'] ?? true;
        $this->showExternal = $options['external'] ?? false;
        $this->showDev = !($options['no-dev'] ?? true);

        $lockData = json_decode(file_get_contents($lockFile), true);
        $packages = [];
        $metaPackage = [];
        foreach ($lockData['packages'] as $package) {
            $package['require'] = $package['require'] ?? [];
            $package['require-dev'] = $package['require-dev'] ?? [];
            if ($package['type'] == 'metapackage') {
                $metaPackage[$package['name']] = $package['require'];
                continue;
            }
            if (isset($package['provide'])) {
                foreach ($package['provide'] as $name => $ver) {
                    $metaPackage[$name] = [$package['name'] => $package['version']];
                }
            }
            $packages[$package['name']] = $package;
        }
        if ($metaPackage) {
            foreach ($packages as &$package) {
                if (empty($package['require'])) {
                    continue;
                }
                foreach ($metaPackage as $metaName => $deps) {
                    if (isset($package['require'][$metaName])) {
                        unset($package['require'][$metaName]);
                        $package['require'] += $deps;
                    }
                    // require-dev ?
                }
            }
        }

        $depends = [];
        $dependsDev = [];
        foreach ($packages as &$package) {
            $require = array_keys($package['require']);
            foreach ($require as $reqName) {
                if (PackageName::fromString($reqName) && isset($packages[$reqName])) {
                    $depends[$reqName][$package['name']] = $package['name'];
                } else {
                    unset($package['require'][$reqName]);
                }
            }
            $requireDev = array_keys($package['require-dev']);
            foreach ($requireDev as $reqName) {
                if (PackageName::fromString($reqName) && isset($packages[$reqName])) {
                    $dependsDev[$reqName][$package['name']] = $package['name'];
                } else {
                    unset($package['require-dev'][$reqName]);
                }
            }
        }
        unset($package);

        foreach ($packages as $package) {
            $pkgName = new PackageName($package['name']);
            $vendor = $this->vendors[$pkgName->vendor()]
                ?? $this->vendors[$pkgName->vendor()] = new Vendor($pkgName->vendor(), $this);
            $vendor->addPackage($package['name']);

            $pkg = new Package(
                $pkgName,
                $package['type'] ?? 'library',
                $package['version'],
                $this
            );

            $pkg->setDist($package['dist']['type'] ?? '', $package['dist']['url'] ?? '');
            foreach ($package['autoload']['psr-4'] ?? [] as $key => $src) {
                $pkg->addNamespace($key);
            }

            foreach ($package['authors'] ?? [] as $packageAuthor) {
                $author = $this->author[$packageAuthor['name']]
                    ?? ($this->author[$packageAuthor['name']] = new Author($packageAuthor['name'], $this));
                $author->add($packageAuthor, $pkgName);
                $pkg->addAuthor($author->name());
                $vendor->addAuthor($author->name());
            }
            $pkg->setDescription($package['description'] ?? '');
            $pkg->setHomePage($package['homepage'] ?? '');
            if (isset($package['source'])) {
                $pkg->addSource($package['source']['type'], $package['source']['url']);
            }

            $this->packages[$package['name']] = $pkg;
            $this->typeSet[$package['type']] = $package['type'];
        }

        foreach ($this->packages as $name => $pkg) {
            $package = $packages[$name];
            foreach ($package['require'] as $reqName => $ver) {
                $relPkg = $this->packages[$reqName];
                $link = new Link($relPkg, isset($package['require-dev'][$reqName]));
                if ($this->canShowLink($link)) {
                    $pkg->addRequirement($link);
                }
            }
            foreach ($depends[$package['name']] ?? [] as $depName) {
                $relPkg = $this->packages[$depName];
                $link = new Link($relPkg, isset($dependsDev[$package['name']][$depName]));
                if ($this->canShowLink($link)) {
                    $pkg->addDependency($link);
                }
            }
        }
    }

    public function get(string $packageName): ?Package
    {
        return $this->packages[$packageName] ?? null;
    }

    public function canShowLink(Link $link): bool
    {
        $package = $link->package();

        return (
            ($this->showLocal && $package->isLocal())
            || ($this->showExternal && !$package->isLocal())
        ) && ($this->showDev || $this->showDev == $link->isDev());
    }

    public function canShowPackage(Package $package): bool
    {
        return $this->canShowLink(new Link($package, $this->showDev));
    }

    public function packages(): array
    {
        $list = [];
        foreach ($this->packages as $package) {
            if ($this->canShowPackage($package)) {
                $list[] = $package;
            }
        }

        return $list;
    }

    public function author(string $authorName): ?Author
    {
        return $this->author[$authorName] ?? null;
    }

    public function vendor(string $vendorName): ?Vendor
    {
        return $this->vendors[$vendorName] ?? null;
    }

    /**
     * @return Author[]
     */
    public function authors(): array
    {
        $list = [];
        foreach ($this->author as $author) {
            if ($author->packages()) {
                $list[] = $author;
            }
        }

        return $list;
    }

    /**
     * @return Vendor[]
     */
    public function vendors(): array
    {
        $list = [];
        foreach ($this->vendors as $vendor) {
            if ($vendor->packages()) {
                $list[] = $vendor;
            }
        }

        return $list;
    }

    public function typeSet(): array
    {
        return $this->typeSet;
    }
}
