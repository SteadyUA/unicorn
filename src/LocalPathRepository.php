<?php

namespace SteadyUa\Unicorn;

use Composer\Package\BasePackage;
use Composer\Package\Loader\ArrayLoader;
use Composer\Package\Package;
use Composer\Package\PackageInterface;
use Composer\Repository\PathRepository;

class LocalPathRepository extends PathRepository
{
    private static $conflicts = [];
    private static $reference = null;

    public static function setUp(array $conflicts, string $reference = null) {
        self::$conflicts = $conflicts;
        self::$reference = $reference;
    }

    /**
     * @param PackageInterface&Package $package
     * @return void
     */
    public function addPackage(PackageInterface $package)
    {
        switch (self::$reference) {
            case 'config':
                $path = realpath($package->getDistUrl()) . DIRECTORY_SEPARATOR;
                $composerFilePath = $path . 'composer.json';
                $ref = sha1(file_get_contents($composerFilePath));
                $package->setDistReference($ref);
                break;

            case 'null':
                $package->setDistReference(null);
                break;
        }

        if (self::$conflicts) {
            $loader = new ArrayLoader();
            $links = $loader->parseLinks(
                $package->getName(),
                $package->getPrettyVersion(),
                BasePackage::$supportedLinkTypes['conflict']['description'],
                self::$conflicts
            );
            $package->setConflicts($links);
        }

        parent::addPackage($package);
    }
}
