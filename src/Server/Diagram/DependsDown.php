<?php

namespace SteadyUa\Unicorn\Server\Diagram;

use SteadyUa\Unicorn\Server\LockReader\LockReader;

class DependsDown
{
    private LockReader $reader;

    public function __construct(LockReader $reader)
    {
        $this->reader = $reader;
    }

    function build(string $packageName): Diagram
    {
        $inspectStack = [$packageName];
        $diagram = new Diagram($packageName);
        while ($inspectStack) {
            $name = array_pop($inspectStack);
            $pkg = $this->reader->get($name);
            if (!$pkg) {
                continue;
            }
            $node = $diagram->add($pkg->name(), $pkg->type(), $pkg->rate());
            foreach ($pkg->require() as $reqName => $link) {
                $node->addRequire($reqName, $link->isDev());
                if (!$diagram->has($reqName) && !in_array($reqName, $inspectStack)) {
                    $inspectStack[] = $reqName;
                }
            }
        }

        return $diagram;
    }
}
