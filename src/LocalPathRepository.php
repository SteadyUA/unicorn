<?php

namespace SteadyUa\Unicorn;

use Composer\Package\Package;
use Composer\Package\PackageInterface;
use Composer\Repository\PathRepository;

class LocalPathRepository extends PathRepository
{
    /**
     * @param PackageInterface&Package $package
     * @return void
     */
    public function addPackage(PackageInterface $package)
    {
        $package->setDistReference(
            sha1_file($package->getDistUrl() . '/composer.json')
        );

        parent::addPackage($package);
    }
}
