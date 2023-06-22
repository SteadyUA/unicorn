<?php

namespace SteadyUa\Unicorn\Server\LockReader;

class Link
{
    private Package $package;
    private bool $isDev;

    public function __construct(Package $package, bool $isDev = false)
    {
        $this->package = $package;
        $this->isDev = $isDev;
    }

    public function package(): Package
    {
        return $this->package;
    }

    public function isDev(): bool
    {
        return $this->isDev;
    }
}
