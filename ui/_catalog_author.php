<?php

use SteadyUa\Unicorn\Server\LockReader\Author;
use SteadyUa\Unicorn\Server\LockReader\Vendor;
use SteadyUa\Unicorn\Server\Tpl;

return Tpl::extends('_catalog.php')
    ->block('nav', function ($v) { ?>
        <div>
            <a href="/?r=<?=$_GET['r']?>&a=_list">Authors</a>
        </div>
        <h1 class="caption"><?=$_GET['a']?>: <?=count($v['author']->packages())?></h1>
<?php })

    ->set('fields', [
        'name',
        'dep',
        'req',
        'type',
        'path',
        'namespace',
    ])

    ->block('content', function ($v) {
        /** @var Author $author */
        $author = $v['author'];
        ?>
        <dl>
            <dt>Name:</dt>
            <dd><?=$author->name()?></dd>
            <?php if ($author->email()) { ?>
            <dt>Email:</dt>
            <dd><?=implode(', ', $author->email())?></dd>
            <?php } ?>
            <?php if ($author->homepage()) { ?>
            <dt>Homepage:</dt>
            <dd><?=implode(', ', array_map(fn ($hp) => '<a href="' . $hp .'">' . $hp . '</a>', $author->homepage()))?></dd>
            <?php } ?>
            <?php if ($vendors = $author->vendors()) { ?>
            <dt>Vendors:</dt>
            <dd><?=implode(', ', array_map(fn (Vendor $v) => '<a href="/?r=' . $_GET['r'] . '&v=' . $v->name() . '">' . $v->name() . '</a>', $vendors))?></dd>
            <?php } ?>
        </dl>
<?php
        $this->parent('content');
    })
    ;
