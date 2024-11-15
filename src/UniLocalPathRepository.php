<?php

namespace SteadyUa\Unicorn;

use Composer\Package\Package;
use Composer\Package\PackageInterface;
use Composer\Repository\PathRepository;

class UniLocalPathRepository extends PathRepository
{
    /**
     * @param PackageInterface&Package $package
     * @return void
     */
    public function addPackage(PackageInterface $package)
    {
        if ($package->getDistType() == 'path') {
            $requires = $package->getRequires();
            foreach ($package->getDevRequires() as $target => $link) {
                $requires[$target] = $link;
            }
            $package->setRequires($requires);
        }

        parent::addPackage($package);
    }
}
