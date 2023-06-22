<?php

namespace SteadyUa\Unicorn\Server\LockReader;

class Vendor
{
    private string $name;
    private array $authors = [];
    private array $packages = [];
    private LockReader $reader;

    public function __construct(string $name, LockReader $reader)
    {
        $this->name = $name;
        $this->reader = $reader;
    }

    public function addPackage(string $packageName): void
    {
        $this->packages[$packageName] = $packageName;
    }

    public function addAuthor(string $authorName): void
    {
        $this->authors[$authorName] = $authorName;
    }

    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return Author[]
     */
    public function authors(): array
    {
        $list = [];
        foreach ($this->authors as $authorName) {
            $author = $this->reader->author($authorName);
            if ($author && $author->packages()) {
                $list[] = $author;
            }
        }

        return $list;
    }

    /**
     * @return Package[]
     */
    public function packages(): array
    {
        $list = [];
        foreach ($this->packages as $packageName) {
            $package = $this->reader->get($packageName);
            if ($package && $this->reader->canShowPackage($package)) {
                $list[] = $package;
            }
        }

        return $list;
    }
}
