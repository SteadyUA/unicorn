<?php

$pathList = [
    __DIR__ . '/../../../autoload.php',
    __DIR__ . '/../vendor/autoload.php',
];
foreach ($pathList as $path) {
    if (file_exists($path)) {
        require $path;
        break;
    }
}

SteadyUa\Unicorn\Server\Server::run(__DIR__);
