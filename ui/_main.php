<?php

use SteadyUa\Unicorn\Server\Tpl;

function load(string $destination, string $source): void
{
    if (!file_exists($destination)) {
        copy($source, $destination);
    }
}

return Tpl::layout(function ($v) {
    $set = $v['typeSet'] ?? [];
    $typeSet = array_diff($set, ['library', 'project', 'metapackage']);
    sort($typeSet);
    $colors = [
        '#eb8a94',
        '#eba18a',
        '#EBCB8B',
        '#bfeb98',
        '#8abbeb',
        '#eb8ad9',
    ];
    $palette = [
        'library' => '#98d9eb',
        'project' => '#8ab6eb',
        'metapackage' => '#dae0eb',
    ];
    foreach ($typeSet as $i => $type) {
        if (isset($colors[$i])) {
            $palette[$type] = $colors[$i];
        }
    }
    ?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php $this->block('title') ?></title>
    <style>
        @import url('./style/main.css');
    <?php $this->block('style') ?>
    <?php foreach ($palette as $type => $color) {
        echo <<<EOT
        .node.$type rect { fill: $color !important }
        .label.$type, .b.$type { background-color: $color }
        .$type::before, .t.$type { color: $color }
        EOT;
    } ?>
    </style>
    <script src="./script/main.js"></script>
    <?php $this->block('head') ?>
</head>
<body>
<?php $this->block("menu", function () { ?>
    <div id="menu">
        <ul>
            <li<?=isset($_GET['p']) ? ' class="current"' : ''?>><a class="i-modules" href="/?r=<?=$_GET['r']?>&p=_list">Packages</a></li>
            <li<?=isset($_GET['a']) ? ' class="current"' : ''?>><a class="i-team" href="/?r=<?=$_GET['r']?>&a=_list">Authors</a></li>
            <li<?=isset($_GET['v']) ? ' class="current"' : ''?>><a class="i-team" href="/?r=<?=$_GET['r']?>&v=_list">Vendors</a></li>
        </ul>
        <div id="options">
            <div class="field">
                <input type="checkbox" id="local" value="K">
                <label for="local">local</label><br>
            </div>
            <div class="field">
                <input type="checkbox" id="external" value="K">
                <label for="external">external</label><br>
            </div>
            <div class="field">
                <input type="checkbox" id="no-dev" value="K">
                <label for="no-dev">No dev</label><br>
            </div>
        </div>
    </div>
    <script>
        initOptions();
    </script>
<?php }) ?>
    <div id="nav">
        <?php $this->block('nav') ?>
    </div>
    <?php $this->block('content') ?>
    <?php $this->block('script') ?>
</body>
</html>
<?php });
