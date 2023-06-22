<?php

namespace SteadyUa\Unicorn\Server\Middleware;

use SteadyUa\Unicorn\Server\Middleware;
use SteadyUa\Unicorn\Server\Tpl;

class HtmlResponseMiddleware implements Middleware
{
    private string $tplDir;

    const NOT_FOUND = '_404.php';

    public function __construct(string $tplDir)
    {
        $this->tplDir = $tplDir;
    }

    public function handle(array $params, callable $next): array
    {
        $params['vars'] = [];
        $params['template'] = self::NOT_FOUND;

        $result = $next($params);

        if ($result['template'] == self::NOT_FOUND) {
            header('HTTP/1.1 404');
        } else {
            header('HTTP/1.1 200');
        }
        header('Content-type: text/html;charset=UTF-8');
        header('Cache-Control: no-cache');

        $oldDir = getcwd();
        chdir($this->tplDir);
        Tpl::render($result['template'] , $result['vars']);
        chdir($oldDir);

        return $params;
    }
}
