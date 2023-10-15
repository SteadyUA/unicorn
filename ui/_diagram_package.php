<?php

use SteadyUa\Unicorn\Server\LockReader\Package;
use SteadyUa\Unicorn\Server\Tpl;

return Tpl::extends('_diagram.php')
    ->block('nav', function ($v) {
        $this->parent('nav'); ?>
        <h1 class="caption"><?=$v['package']->name()?> <?=$v['package']->version()?></h1>
<?php })

    ->block('content', function ($v) {
        /** @var Package $package */
        $package = $v['package']; ?>
        <div class="info">
            <div class="desc">
                <dl>
                    <dt>Type:</dt>
                    <dd><span class="t <?=$package->type()?>"><?=$package->type()?></span></dd>
                    <dt>Namespace:</dt>
                    <dd><?=implode(', ', $package->namespaces())?></dd>
                    <dt>Path:</dt>
                    <dd><span class="uni-root"><?=$_GET['r']?>/</span><?=str_replace('uni_vendor/', '<span class="uni">uni_vendor</span>/', $package->path())?></dd>
                    <?php if ($package->authors()) { ?>
                        <dt>Authors:</dt>
                        <dd><?php
                            $list = [];
                            foreach ($package->authors() as $author) {
                                $list[] = '<a href="/?r=' . $_GET['r'] . '&a=' . $author->name() . '">' . $author->name() . '</a>';
                            }
                            echo implode(', ', $list);
                            ?></dd>
                    <?php } ?>
                    <?php if ($package->description()) { ?>
                        <dt>Description:</dt>
                        <dd><?=$package->description()?></dd>
                    <?php } ?>

                    <?php if ($package->homepage()) { ?>
                        <dt>Homepage:</dt>
                        <dd><a href="<?=$package->homepage()?>"><?=$package->homepage()?></a></dd>
                    <?php } ?>
                    <?php if ($package->sources()) { ?>
                        <dt>Source:</dt>
                        <dd>
                            <?php foreach ($package->sources() as $type => $url) {?>
                            <?=$type?> <a href="<?=$url?>"><?=$url?></a>
                            <?php } ?>
                        </dd>
                    <?php } ?>
                    <dt>Vendor:</dt>
                    <dd>
                        <a href="/?r=<?=$_GET['r']?>&v=<?=$package->vendor()->name()?>"><?=$package->vendor()->name()?></a>
                    </dd>
                </dl>
            </div>
            <div class="deps">
                <h3>Dependents: <?=count($package->depends())?></h3>
                <ul>
                    <?php foreach($package->depends() as $name => $link) {?>
                        <li class="<?=$link->package()->type()?>">
                            <a href="<?=ln($name)?>" class="<?=$link->isDev() ? 'dev' : ''?>"><?=$name?></a> (<?=$link->package()->rate()?>)
                        </li>
                    <?php } ?>
                </ul>
                <div><a href="<?=ln($package->name(), 'up')?>" class="d-up">Dependents from above: <?=count($v['diagramUp']->nodes()) - 1?></a></div>
            </div>
            <div class="reqs">
                <h3>Requirements: <?=count($package->require())?></h3>
                <ul>
                    <?php foreach($package->require() as $name => $link) {?>
                        <li class="<?=$link->package()->type()?>">
                            <a href="<?=ln($name)?>" class="<?=$link->isDev() ? 'dev' : ''?>"><?=$name?></a> (<?=$link->package()->rate()?>)
                        </li>
                    <?php } ?>
                </ul>
                <div><a href="<?=ln($package->name(), 'down')?>" class="d-down">Requirements to the very bottom: <?=count($v['diagramDown']->nodes()) - 1?></a></div>
            </div>
        </div>
        <h2 class="info-h2">Dependency diagram</h2>
<?php
        $this->parent('content');
    });
