<?php

namespace SteadyUa\Unicorn\Server\LockReader;

class Author
{
    private string $name;
    private array $email = [];
    private array $homepage = [];
    private array $packages = [];
    private array $vendors = [];
    private LockReader $reader;

    public function __construct(string $name, LockReader $reader)
    {
        $this->name = $name;
        $this->reader = $reader;
    }

    public function add(array $author, PackageName $packageName): void
    {
        if (isset($author['email']) && !in_array($author['email'], $this->email)) {
            $this->email[] = $author['email'];
        }
        if (isset($author['homepage']) && !in_array($author['homepage'], $this->homepage)) {
            $this->homepage[] = $author['homepage'];
        }
        if (!in_array($packageName->value(), $this->packages)) {
            $this->packages[] = $packageName->value();
        }
        if (!in_array($packageName->vendor(), $this->vendors)) {
            $this->vendors[] = $packageName->vendor();
        }
    }

    public function name(): string
    {
        return $this->name;
    }

    public function email(): array
    {
        return $this->email;
    }

    public function homepage(): array
    {
        return $this->homepage;
    }

    /**
     * @return Package[]
     */
    public function packages(): array
    {
        $list = [];
        foreach ($this->packages as $packageName) {
            $pkg = $this->reader->get($packageName);
            if ($pkg && $this->reader->canShowPackage($pkg)) {
                $list[] = $pkg;
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
        foreach ($this->vendors as $vendorName) {
            $vendor = $this->reader->vendor($vendorName);
            if ($vendor->packages()) {
                $list[] = $vendor;
            }
        }

        return $list;
    }
}
