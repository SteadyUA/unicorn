<?php

namespace SteadyUa\Unicorn\Server\Diagram;

interface RenderInterface
{
    public function render(Diagram $diagram): string;
}
