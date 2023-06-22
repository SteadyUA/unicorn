<?php

namespace SteadyUa\Unicorn\Server\Middleware;

use SteadyUa\Unicorn\Server\LockReader\LockReader;
use SteadyUa\Unicorn\Server\Middleware;

class InitReaderMiddleware implements Middleware
{
    public function handle(array $params, callable $next): array
    {
        $root = $_GET['r'] ?? '';
        $lockFile = $this->findLockFile($root);
        if (!$lockFile) {
            return $params;
        }
        $reader = new LockReader(
            $lockFile,
            json_decode($_COOKIE['options'] ?? '{}', true)
        );
        $params['lockReader'] = $reader;
        $params['vars']['typeSet'] = $reader->typeSet();

        return $next($params);
    }

    private function findLockFile(string $dir): ?string
    {
        $files = glob($dir . '/*.lock');
        if ($files) {
            return $files[0];
        }

        return null;
    }
}
