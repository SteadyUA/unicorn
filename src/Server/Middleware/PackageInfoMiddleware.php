<?php

namespace SteadyUa\Unicorn\Server\Middleware;

use SteadyUa\Unicorn\Server\Diagram\DependsBoth;
use SteadyUa\Unicorn\Server\Diagram\DependsDown;
use SteadyUa\Unicorn\Server\Diagram\DependsUp;
use SteadyUa\Unicorn\Server\Diagram\MermaidRender;
use SteadyUa\Unicorn\Server\LockReader\LockReader;
use SteadyUa\Unicorn\Server\Middleware;

class PackageInfoMiddleware implements Middleware
{
    public function handle(array $params, callable $next): array
    {
        if (!isset($_GET['p'])) {
            return $next($params);
        }

        /** @var LockReader $lockReader */
        $lockReader = $params['lockReader'];
        if ('_list' == $_GET['p']) {
            $params['template'] = '_catalog.php';
            $params['vars']['packages'] = $lockReader->packages();
            return $params;
        }

        $packageName = $_GET['p'];
        $package = $lockReader->get($packageName);
        if (!$package) {
            return $params;
        }
        $vars = [
            'package' => $package,
        ];
        $render = new MermaidRender();
        switch ($_GET['d'] ?? '') {
            case 'up':
                $dependsUp = new DependsUp($lockReader);
                $diagramUp = $dependsUp->build($packageName);
                $vars['dependencyDiagram'] =  $render->render($diagramUp, true);
                $vars['diagram'] = $diagramUp;
                $template = '_diagram_complete.php';
                break;

            case 'down':
                $dependsDown = new DependsDown($lockReader);
                $diagramDown = $dependsDown->build($packageName);
                $vars['dependencyDiagram'] =  $render->render($diagramDown);
                $vars['diagram'] = $diagramDown;
                $template = '_diagram_complete.php';
                break;

            default:
                $dependsUp = new DependsUp($lockReader);
                $dependsDown = new DependsDown($lockReader);
                $depends = new DependsBoth($lockReader);
                $diagramUp = $dependsUp->build($packageName);
                $diagramDown = $dependsDown->build($packageName);
                $diagram = $depends->build($packageName);
                $vars['dependencyDiagram'] = $render->render($diagram);
                $vars['diagram'] = $diagram;
                $vars['diagramUp'] = $diagramUp;
                $vars['diagramDown'] = $diagramDown;
                $template = '_diagram_package.php';
        }

        $params['vars'] += $vars;
        $params['template'] = $template;

        return $params;
    }
}
