<?php

namespace SteadyUa\Unicorn\Server\Middleware;

use SteadyUa\Unicorn\Server\LockReader\LockReader;
use SteadyUa\Unicorn\Server\Middleware;

class VendorMiddleware implements Middleware
{
    public function handle(array $params, callable $next): array
    {
        if (!isset($_GET['v'])) {
            return $next($params);
        }

        /** @var LockReader $lockReader */
        $lockReader = $params['lockReader'];
        if ('_list' == $_GET['v']) {
            $params['vars']['vendors'] = $lockReader->vendors();
            $params['template'] = '_catalog_vendor-list.php';

            return $params;
        }

        $vendor = $lockReader->vendor($_GET['v']);
        if (!$vendor) {
            return $params;
        }

        $params['vars']['vendor'] = $vendor;
        $params['vars']['packages'] = $vendor->packages();
        $params['template'] = '_catalog_vendor.php';

        return $params;
    }
}
