<?php

namespace SteadyUa\Unicorn\Action;

use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use RuntimeException;
use SteadyUa\Unicorn\Utils;

class InstallAction extends AbstractAction
{
    private string $names;
    private Utils $utils;

    /** @var PackageInterface[] */
    private array $packages;

    /**
     * @param Utils $utils
     * @param PackageInterface[] $installPackages
     */
    public function __construct(Utils $utils, array $installPackages)
    {
        $names = [];
        $this->packages = [];
        foreach ($installPackages as $installPackage) {
            $names[] = $installPackage->getName();
            $this->packages[] = $installPackage;
        }
        $this->names = implode(' ', $names);
        $this->utils = $utils;
    }

    public function exec(IOInterface $io): void
    {
        $io->write('<info>installing packages:</info> ' . $this->names);
        $res = $this->utils->install($this->packages);
        if ($res > 0) {
            throw new RuntimeException('installing failed', -1);
        }

        parent::exec($io);
    }

    public function undo(IOInterface $io): void
    {
        $io->write('<info>installing after rollback</info> ');
        $res = $this->utils->install($this->packages);
        if ($res > 0) {
            throw new RuntimeException('installing after rollback failed', -2);
        }

        parent::undo($io);
    }
}
