<?php

namespace SteadyUa\Unicorn\Server;

interface Middleware
{
    public function handle(array $params, callable $next): array;
}
