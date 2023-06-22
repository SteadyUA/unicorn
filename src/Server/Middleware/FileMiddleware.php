<?php

namespace SteadyUa\Unicorn\Server\Middleware;

use SteadyUa\Unicorn\Server\Middleware;

class FileMiddleware implements Middleware
{
    private string $httpRootDir;

    public function __construct(string $httpRootDir)
    {
        $this->httpRootDir = $httpRootDir;
    }

    public function handle(array $params, callable $next): array
    {
        $requestPath = $this->httpRootDir . '/' . substr($_SERVER['SCRIPT_NAME'], 1);
        if (!(file_exists($requestPath) && !is_dir($requestPath))) {
            return $next($params);
        }
        $path = explode('.', $requestPath);
        $contentType = [
            'htm' => 'text/html;charset=UTF-8',
            'html' => 'text/html;charset=UTF-8',
            'js' => 'text/javascript;charset=UTF-8',
            'mjs' => 'text/javascript;charset=UTF-8',
            'css' => 'text/css;charset=UTF-8',
            'ico' => 'image/ico',
            'png' => 'image/png',
        ];
        header('Content-type: ' . $contentType[array_pop($path)] ?? 'text/plain');
        header('Cache-Control: public, max-age=604800');

        fpassthru(fopen($requestPath, 'rb'));

        return $params;
    }
}
