<?php

use SteadyUa\Unicorn\Server\LockReader\Author;
use SteadyUa\Unicorn\Server\LockReader\Vendor;
use SteadyUa\Unicorn\Server\Tpl;

return Tpl::extends('_catalog.php')
    ->block('nav', function ($v) { ?>
        <h1>Vendors: <?=count($v['vendors'])?></h1>
<?php })
    ->set('fields', [
        'name',
        'packages',
        'authors',
        'authorsCount',
    ])
    ->block('content', function ($v) {
        $fields = $this->get('fields');
        ?>
        <table class="table">
            <thead>
            <tr>
                <?php foreach ($fields as $field) { ?>
                    <th><?=$field?></th>
                <?php } ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($v['vendors'] as $vendor) {
                /** @var Vendor $vendor */
                $row = [
                    'name' => '<a href="/?r=' . $_GET['r'] . '&v=' . $vendor->name() . '">' . $vendor->name() . '</a>',
                    'packages' => count($vendor->packages()),
                    'authors' => implode(', ', array_map(fn (Author $author) => '<a href="/?r=' . $_GET['r'] . '&a=' . $author->name() . '">' . $author->name() . '</a>', $vendor->authors())),
                    'authorsCount' => count($vendor->authors()),
                ];
                ?>
                <tr>
                    <?php foreach ($fields as $field) { ?>
                        <td><?=$row[$field]?></td>
                    <?php } ?>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    <?php })
    ;
