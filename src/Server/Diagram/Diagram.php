<?php

namespace SteadyUa\Unicorn\Server\Diagram;

use Generator;

class Diagram
{
    /** @var array<Node> */
    private array $nodes = [];
    private int $index = 1;
    private ?string $current;

    public function __construct(string $current = null)
    {
        $this->current = $current;
    }

    public function has(string $name): bool
    {
        return isset($this->nodes[$name]);
    }

    public function add(string $name, string $type, string $ration): Node
    {
        if ($this->has($name)) {
            return $this->nodes[$name];
        }

        return $this->nodes[$name] = new Node('D' . $this->index++, $name, $type, $ration);
    }

    public function get(string $name): ?Node
    {
        return $this->nodes[$name] ?? null;
    }

    /**
     * @return array<Node>
     */
    public function nodes(): array
    {
        return $this->nodes;
    }

    /**
     * @return Generator<Relation>
     */
    public function relations(): Generator
    {
        foreach ($this->nodes() as $node) {
            foreach ($node->require() as $reqName => $isDev) {
                if (isset($this->nodes[$reqName])) {
                    yield new Relation($node, $this->nodes[$reqName], $isDev);
                }
            }
        }
    }

    public function current(): ?string
    {
        return $this->current;
    }
}
