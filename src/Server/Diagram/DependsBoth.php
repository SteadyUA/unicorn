<?php

namespace SteadyUa\Unicorn\Server\Diagram;

use SteadyUa\Unicorn\Server\LockReader\LockReader;

class DependsBoth
{
    private LockReader $reader;

    public function __construct(LockReader $reader)
    {
        $this->reader = $reader;
    }

    function build(string $packageName): Diagram
    {
        $diagram = new Diagram($packageName);
        $pkg = $this->reader->get($packageName);
        foreach ($pkg->depends() as $name => $link) {
            $node = $diagram->add($name, $link->package()->type(), $link->package()->rate());
            $node->addRequire($packageName, $link->isDev());
        }
        $node = $diagram->add($pkg->name(), $pkg->type(), $pkg->rate());
        foreach ($pkg->require() as $name => $link) {
            $diagram->add($name, $link->package()->type(), $link->package()->rate());
            $node->addRequire($name, $link->isDev());
        }

        $nodes = $diagram->nodes();
        foreach ($nodes as $node) {
            $pkg = $this->reader->get($node->name());
            foreach ($pkg->require() as $name => $link) {
                if (isset($nodes[$name])) {
                    $node->addRequire($name, $link->isDev());
                }
            }
        }

        return $diagram;
    }
}
