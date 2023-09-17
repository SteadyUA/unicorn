<?php

use SteadyUa\Unicorn\Server\Tpl;

return Tpl::extends('_diagram.php')
->block('nav', function ($v) {
    $this->parent('nav'); ?>
    <h1 class="caption"><a href="<?=ln($v['package']->name())?>"><?=$v['package']->name()?> <?=$v['package']->version()?></a></h1>
    <h2 class="d-<?=$_GET['d']?> caption"><?=($_GET['d'] == 'down' ? "requirements down" : "dependencies from top")?>: <?=count($v['diagram']->nodes()) - 1?></h2>
<?php })

->block('content', function ($v) {
    $byType = [];
    $current = null;
    foreach ($v['diagram']->nodes() as $node) {
        if ($node->name() != $v['package']->name()) {
            $byType[$node->type()][] = $node;
        } else {
            $current = $node;
        }
    }
    ?>
    <table>
        <tr>
            <?php foreach ($byType as $typeName => $list) { ?>
                <th><span class="t <?=$typeName?>"><?=$typeName?></span>: <?=count($list)?></th>
            <?php } ?>
        </tr>
        <tr>
            <?php foreach ($byType as $list) { ?>
                <td>
                    <?php foreach ($list as $node) {
                        $class = $current->isDependsOn($node) ? ' class="active"' : '';
                        ?>
                        <div<?=$class?>><a href="<?=ln($node->name())?>"><?=$node->name()?></a> (<?=$node->ration()?>)</div>
                    <?php } ?>
                </td>
            <?php } ?>
        </tr>
    </table>
<?php $this->parent('content');
})
;
