<?php

namespace SteadyUa\Unicorn\Server;

use SteadyUa\Unicorn\Server\Middleware\AuthorMiddleware;
use SteadyUa\Unicorn\Server\Middleware\FileMiddleware;
use SteadyUa\Unicorn\Server\Middleware\InitReaderMiddleware;
use SteadyUa\Unicorn\Server\Middleware\HtmlResponseMiddleware;
use SteadyUa\Unicorn\Server\Middleware\PackageInfoMiddleware;
use SteadyUa\Unicorn\Server\Middleware\VendorMiddleware;

class Server
{
    public static function run(string $httpRootDir)
    {
        self::handleRequest(
            new FileMiddleware($httpRootDir),
            new HtmlResponseMiddleware($httpRootDir),
            new InitReaderMiddleware(),
            new PackageInfoMiddleware(),
            new AuthorMiddleware(),
            new VendorMiddleware(),
        );
    }

    private static function handleRequest(Middleware ... $mwStack): void
    {
        $next = fn ($params) => $params;
        while ($mwStack) {
            $mw = array_pop($mwStack);
            $next = fn ($params) => $mw->handle($params, $next);
        }
        $next([]);
    }
}
