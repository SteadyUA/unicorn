<?php

use SteadyUa\Unicorn\Server\LockReader\Author;
use SteadyUa\Unicorn\Server\LockReader\Package;
use SteadyUa\Unicorn\Server\Tpl;

return Tpl::extends('_main.php')
    ->block('title', function () {
        echo 'Packages';
    })
    ->block('nav', function ($v) {
        echo '<h1>Packages: ' . count($v['packages']) . '</h1>';
    })
    ->set('fields', [
        'name',
        'ver',
        'dep',
        'req',
        'type',
        'path',
        'namespace',
        'authors',
    ])
    ->block('content', function ($v) {
        $fields = $this->get('fields'); ?>
        <table class="table">
            <thead>
            <tr>
                <?php foreach ($fields as $field) { ?>
                    <th><?=$field?></th>
                <?php } ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($v['packages'] as $pkg) {
                /** @var Package $pkg */
                $package = [
                    'namespace' => implode('<br/>', $pkg->namespaces()),
                    'path' => str_replace('uni_vendor/', '<span class="uni">uni_vendor</span>/', $pkg->path()),
                    'dep' => count($pkg->depends()),
                    'req'=> count($pkg->require()),
                    'ver' => $pkg->version(),
                    'name' => '<a href="/?r=' . $_GET['r']
                        . '&p=' . $pkg->name() . '" class="type ' . $pkg->type() . '">'
                        . $pkg->name()
                        . '</a>',
                    'type' => '<span class="t ' . $pkg->type() . '">' . $pkg->type() . '</span>',
                    'authors' => implode(
                        ', ',
                        array_map(fn(Author $a) => '<a href="/?r=' . $_GET['r'] . '&a=' . $a->name() . '">' . $a->name() . '</a>', $pkg->authors())
                    ),
                ];
                ?>
                <tr>
                    <?php foreach ($fields as $field) { ?>
                        <td><?=$package[$field]?></td>
                    <?php } ?>
                </tr>
            <?php } ?>
            </tbody>
        </table>
<?php })

    ->block('style', function () {
        echo "@import url('./style/table.css');";
    })

    ->block('head', function () {?>
        <?php load('./script/cache.simple-datatables.js', 'https://cdn.jsdelivr.net/npm/simple-datatables@7.2.0') ?>
        <script src="./script/cache.simple-datatables.js"></script>
    <?php })

    ->block('script', function () { ?>
        <script>
            let perPage = 25;
            const table = new simpleDatatables.DataTable(
                "table", {
                    perPage: perPage,
                    perPageSelect: [25, 50, 100],
                }
            );
            let params = Object.fromEntries(
                new URLSearchParams(window.location.hash.substring(1))
            )
            let ignore = false;
            function pushState() {
                history.pushState(
                    params,
                    document.title,
                    '#' + Object.keys(params).map((key) => key + '=' + params[key]).join('&')
                );
            }
            function restore(params) {
                ignore = true;
                if (params.search) {
                    table.search(params.search)
                    let input = document.getElementsByClassName("datatable-input")[0];
                    input.value = params.search;
                }
                if (params.sort) {
                    let column, direction;
                    [column, direction] = params.sort.split(':');
                    table.columns.sort(column * 1, direction);
                }
                if (params.page) {
                    let pp, p;
                    [pp, p] = params.page.split(':');
                    perPage = pp * 1;
                    let input = document.getElementsByClassName("datatable-selector")[0];
                    input.value = perPage;
                    input.dispatchEvent(new Event('change'));
                    table.page(p * 1)
                }
                ignore = false;
            }
            table.on('datatable.sort', function(column, direction) {
                if (ignore) return;
                params.sort = column + ':' + direction;
                params.page = perPage + ':1';
                pushState();
            });
            table.on('datatable.search', function(query) {
                if (ignore) return;
                params.search = query;
            });
            let input = document.getElementsByClassName("datatable-input")[0];
            input.addEventListener('blur', function () {
                params.search = input.value;
                pushState();
            });
            table.on('datatable.perpage', function(pp) {
                if (ignore) return;
                perPage = pp;
                params.page = pp + ':1';
                pushState();
            });
            table.on('datatable.page', function(page) {
                if (ignore) return;
                params.page = perPage + ':' + page;
                pushState();
            });
            window.onpopstate = function (e) {
                if (!e.state) {
                    location.reload();
                    return;
                }
                restore(e.state);
            };
            restore(params);
        </script>
<?php })
    ;
