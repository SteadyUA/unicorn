<?php

namespace SteadyUa\Unicorn\Server\LockReader;

class PackageName
{
    private string $vendor;
    private string $name;

    public function __construct(string $packageName)
    {
        [$this->vendor, $this->name] = explode('/', $packageName);
    }

    public function value(): string
    {
        return $this->vendor . '/' . $this->name;
    }

    public static function isValid(string $packageName): bool
    {
        return strpos($packageName, '/') !== false;
    }

    public static function fromString(string $packageName): ?PackageName
    {
        if (self::isValid($packageName)) {
            return new self($packageName);
        }

        return null;
    }

    public function vendor(): string
    {
        return $this->vendor;
    }

    public function name(): string
    {
        return $this->name;
    }
}
