<?php

use SteadyUa\Unicorn\Server\Tpl;

return Tpl::extends('_main.php')
    ->block('menu', function () {

    })
    ->block('title', function () {
        echo '404 Not found';
    })
    ->block('nav', function () { ?>
        <h1>404 Not found</h1>
<?php })
    ->block('content', function () { ?>
        <p>The requested URL was not found on this server.</p>
<?php })
    ;
