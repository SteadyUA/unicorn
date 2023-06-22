<?php

use SteadyUa\Unicorn\Server\Tpl;

function ln(string $packageName, string $action = ''): string
{
    return '/?r=' . $_GET['r'] . '&p=' . $packageName . ($action ? '&d=' . $action : '');
}

return Tpl::extends('_main.php')
    ->block('title', function ($v) {
        echo $v['package']->name();
    })

    ->block('nav', function () { ?>
        <div>
            <a href="/?r=<?=$_GET['r']?>&p=_list">Packages</a>
        </div>
<?php })

    ->block('content', function ($v) { ?>
        <pre class="mermaid"><?=$v['dependencyDiagram']?></pre>
<?php })

    ->block('head', function () {
        load('./script/cache.mermaid.min.js', 'https://cdn.jsdelivr.net/npm/mermaid@9.4.3/dist/mermaid.min.js'); ?>
        <script src="./script/cache.mermaid.min.js"></script>
    <?php })

    ->block('script', function ($v) {
        $links = [];
        foreach ($v['diagram']->nodes() as $node) {
            $links[$node->index()] = $node->name();
        } ?>
    <script>
        let config = {
            startOnLoad: true,
            theme: 'base',
            themeVariables: { // https://mermaid-js.github.io/mermaid/#/theming
                primaryColor: '#dae0eb',
                lineColor: '#4C566A',
                primaryTextColor: '#2E3440'
            }
        };
        mermaid.initialize(config);
        const el = document.querySelector('.mermaid');
        const dlinks = <?=json_encode($links)?>;
        el.addEventListener('click', function (event) {
            let node = null;
            let e = event.target;
            while (e) {
                if (e.classList && e.classList.contains('node')) {
                    node = e.id.split('-')[1] ?? null;
                    break;
                }
                e = e.parentNode;
            }
            if (node) {
                let u = '/?r=<?=$_GET['r']?>&p=' + dlinks[node];
                if (event.ctrlKey) {
                    event.preventDefault();
                    window.open(u, '_blank');
                } else {
                    document.location = u;
                }
            }
        });
    </script>
<?php
    })
    ;
