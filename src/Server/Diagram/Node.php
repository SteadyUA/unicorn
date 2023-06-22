<?php

namespace SteadyUa\Unicorn\Server\Diagram;

class Node
{
    private string $index;
    private string $name;
    private string $type;
    private string $ration;
    private array $require = [];

    public function __construct(string $index, string $name, string $type, string $ration)
    {
        $this->name = $name;
        $this->type = $type;
        $this->index = $index;
        $this->ration = $ration;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function index(): string
    {
        return $this->index;
    }

    public function addRequire(string $require, bool $isDev = false)
    {
        $this->require[$require] = $isDev;
    }

    public function require(): array
    {
        return $this->require;
    }

    public function ration(): string
    {
        return $this->ration;
    }

    public function isDependsOn(Node $node): bool
    {
        return isset($this->require[$node->name]);
    }
}
