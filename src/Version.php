<?php

namespace SteadyUa\Unicorn;

class Version
{
    /** @var string[] */
    private $ver;

    public function __construct(string $version)
    {
        $ver = explode('.', $version);
        while (count($ver) > 3) {
            array_pop($ver);
        }
        if (!isset($ver[1])) {
            $ver[1] = 0;
        }
        if (!isset($ver[2])) {
            $ver[2] = 0;
        }
        $this->ver = $ver;
    }

    public function value(): array
    {
        return $this->ver;
    }

    public function __toString(): string
    {
        return implode('.', $this->ver);
    }

    public function patch(): self
    {
        $version = clone $this;
        $version->ver[2] ++;

        return $version;
    }

    public function minor(): self
    {
        $version = clone $this;
        $version->ver[1] ++;
        $version->ver[2] = 0;

        return $version;
    }

    public function major(): self
    {
        $version = clone $this;
        $version->ver[0] ++;
        $version->ver[1] = 0;
        $version->ver[2] = 0;

        return $version;
    }

    public function minorConstraint(): string
    {
        return "^{$this->ver[0]}.{$this->ver[1]}";
    }
}
