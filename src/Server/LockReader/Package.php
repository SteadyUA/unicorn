<?php

namespace SteadyUa\Unicorn\Server\LockReader;

class Package
{
    private string $name;
    private string $type;
    private string $version;
    private array $authors = [];
    private string $path = '';
    private array $namespaces = [];
    private string $description = '';
    private bool $isLocal = false;
    private bool $isDev = false;
    private string $homepage = '';
    private array $sources = [];
    /** @var Link[]  */
    private array $depends = [];
    /** @var Link[] */
    private array $require = [];
    private LockReader $reader;
    private string $vendor;

    public function __construct(
        PackageName $name,
        string $type,
        string $version,
        LockReader $reader
    ) {
        $this->name = $name->value();
        $this->vendor = $name->vendor();
        $this->type = $type;
        $this->version = $version;
        $this->reader = $reader;
    }

    public function rate(): string
    {
        return count($this->depends()) . '/' . count($this->require());
    }

    public function addRequirement(Link $rel): void
    {
        $this->require[$rel->package()->name()] = $rel;
    }

    public function addDependency(Link $rel): void
    {
        $this->depends[$rel->package()->name()] = $rel;
    }

    public function addSource(string $type, string $url): void
    {
        $this->sources[$type] = $url;
    }

    public function setHomePage(string $homepage): void
    {
        $this->homepage = $homepage;
    }

    public function setDev(bool $isDev = true): void
    {
        $this->isDev = $isDev;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function addNamespace(string $namespace): void
    {
        $this->namespaces[] = $namespace;
    }

    public function setDist(string $type, string $url): void
    {
        if ($type == 'path') {
            $path = str_replace('./', '', $url);
            $this->isLocal = true;
        } else {
            $path = 'uni_vendor/' . $this->name;
        }

        $this->path = $path;
    }

    public function addAuthor(string $author): void
    {
        $this->authors[$author] = $author;
    }

    /**
     * @return Author[]
     */
    public function authors(): array
    {
        $list = [];
        foreach ($this->authors as $authorName) {
            $author = $this->reader->author($authorName);
            if ($author) {
                $list[] = $author;
            }
        }

        return $list;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function namespaces(): array
    {
        return $this->namespaces;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function isLocal(): bool
    {
        return $this->isLocal;
    }

    public function version(): string
    {
        return $this->version;
    }

    public function isDev(): bool
    {
        return $this->isDev;
    }

    public function homepage(): string
    {
        return $this->homepage;
    }

    public function sources(): array
    {
        return $this->sources;
    }

    /**
     * @return Link[]
     */
    public function depends(): array
    {
        return $this->depends;
    }

    /**
     * @return Link[]
     */
    public function require(): array
    {
        return $this->require;
    }

    public function vendor(): Vendor
    {
        return $this->reader->vendor($this->vendor);
    }
}
