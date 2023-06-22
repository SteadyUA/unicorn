<?php

namespace SteadyUa\Unicorn\Server\Diagram;

class Relation
{
    private Node $from;
    private Node $to;
    private bool $isDev;

    public function __construct(Node $from, Node $to, bool $isDev = false)
    {
        $this->from = $from;
        $this->to = $to;
        $this->isDev = $isDev;
    }

    public function from(): Node
    {
        return $this->from;
    }

    public function to(): Node
    {
        return $this->to;
    }

    public function isDev(): bool
    {
        return $this->isDev;
    }
}
