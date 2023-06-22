<?php

use SteadyUa\Unicorn\Server\LockReader\Author;
use SteadyUa\Unicorn\Server\LockReader\Vendor;
use SteadyUa\Unicorn\Server\Tpl;

return Tpl::extends('_catalog.php')
    ->block('nav', function ($v) { ?>
        <h1>Authors: <?=count($v['authors'])?></h1>
<?php })
    ->set('fields', [
        'name',
        'packages',
        'email',
        'homepage',
        'vendors',
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
            <?php foreach ($v['authors'] as $author) {
                /** @var Author $author */
                $row = [
                    'name' => '<a href="/?r=' . $_GET['r'] . '&a=' . $author->name() . '">' . $author->name() . '</a>',
                    'packages' => count($author->packages()),
                    'email' => implode(', ', $author->email()),
                    'homepage' => implode(', ', array_map(fn ($hp) => '<a href="' . $hp .'">' . $hp . '</a>', $author->homepage())),
                    'vendors' => implode(', ', array_map(fn (Vendor $v) => '<a href="/?r=' . $_GET['r'] . '&v=' . $v->name() . '">' . $v->name() . '</a>', $author->vendors())),
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
