<?php

namespace SteadyUa\Unicorn\Cmd;

use Composer\IO\IOInterface;
use RuntimeException;
use SteadyUa\Unicorn\Utils;

class RunScriptsCmd extends AbstractCmd
{
    private $scripts;
    private $utils;
    private $packages;

    public function __construct(Utils $utils, array $scripts, array $packages)
    {
        $this->scripts = $scripts;
        $this->utils = $utils;
        $this->packages = $packages;
    }

    public function exec(IOInterface $io): void
    {
        $names = implode(' ', $this->scripts);
        $io->write('<info>runing scripts (post-update-scripts):</info> ' . $names);
        $res = $this->utils->runScripts($this->scripts, $this->packages);
        if ($res > 0) {
            throw new RuntimeException('scripts failed', -1);
        }

        parent::exec($io);
    }
}
