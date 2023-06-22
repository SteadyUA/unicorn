<?php

namespace SteadyUa\Unicorn\Server\Middleware;

use SteadyUa\Unicorn\Server\LockReader\LockReader;
use SteadyUa\Unicorn\Server\Middleware;

class AuthorMiddleware implements Middleware
{
    public function handle(array $params, callable $next): array
    {
        if (!isset($_GET['a'])) {
            return $next($params);
        }

        /** @var LockReader $lockReader */
        $lockReader = $params['lockReader'];
        if ('_list' == $_GET['a']) {
            $params['template'] = '_catalog_author-list.php';
            $params['vars']['authors'] = $lockReader->authors();

            return $params;
        }

        $author = $lockReader->author($_GET['a']);
        if (!$author) {
            return $params;
        }

        $params['template'] = '_catalog_author.php';
        $params['vars']['author'] = $author;
        $params['vars']['packages'] = $author->packages();

        return $params;
    }
}
