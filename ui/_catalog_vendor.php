<?php

use SteadyUa\Unicorn\Server\LockReader\Author;
use SteadyUa\Unicorn\Server\LockReader\Vendor;
use SteadyUa\Unicorn\Server\Tpl;

return Tpl::extends('_catalog.php')
    ->block('nav', function ($v) { ?>
        <div>
            <a href="/?r=<?=$_GET['r']?>&v=_list">Vendors</a>
        </div>
        <h1 class="caption"><?=$_GET['v']?>: <?=count($v['packages'])?></h1>
<?php })

    ->block('content', function ($v) {
        /** @var Vendor $vendor */
        $vendor = $v['vendor'];
        ?>
        <dl>
            <dt>Name:</dt>
            <dd><?=$vendor->name()?></dd>
            <dt>Authors:</dt>
            <dd><?=implode(', ', array_map(fn (Author $author) => '<a href="/?r=' . $_GET['r'] . '&a=' . $author->name() . '">' . $author->name() . '</a>', $vendor->authors()))?></dd>
        </dl>
<?php
        $this->parent('content');
    })
    ;
