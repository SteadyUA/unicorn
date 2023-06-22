<?php

namespace SteadyUa\Unicorn\Server\Diagram;

class MermaidRender implements RenderInterface
{
    public function render(Diagram $diagram, bool $reverse = false): string
    {
        $lines = ['graph LR'];
        foreach ($diagram->nodes() as $node) {
            $lines[] = $node->index()
                . '["«' . $node->type() . '» (' . $node->ration() . ')\n' . $node->name() . '"]'
                . ':::' . $node->type();
        }
        if ($diagram->current()) {
            $lines[] = 'style ' . $diagram->get($diagram->current())->index() . ' stroke:#333,stroke-width:2px';
        }
        if ($reverse) {
            foreach ($diagram->relations() as $rel) {
                $relType = $rel->isDev() ? '-.->' : '-->';
                $lines[] = $rel->to()->index() . $relType . $rel->from()->index();
            }
        } else {
            foreach ($diagram->relations() as $rel) {
                $relType = $rel->isDev() ? '-.->' : '-->';
                $lines[] = $rel->from()->index() . $relType . $rel->to()->index();
            }
        }

        return implode("\n", $lines) . "\n";
    }
}
