<?php

namespace SteadyUa\Unicorn;

use Composer\Json\JsonFile;
use Composer\Repository\ConfigurableRepositoryInterface;
use Composer\Repository\FilterRepository;
use Composer\Repository\RepositoryManager;

class Provider
{
    /** @var array */
    private $config = [];

    /** @var PathUtil */
    private $pathUtil;

    public function __construct(string $cwd)
    {
        $this->pathUtil = new PathUtil($cwd);
        $path = $cwd;
        $home = realpath(getenv('HOME') ?: getenv('USERPROFILE') ?: '/');
        while (dirname($path) !== $path && $path != $home) {
            $unicornPath = $path . '/unicorn.json';
            if (file_exists($unicornPath)) {
                $this->config = (new JsonFile($unicornPath))->read();
                $this->config['path'] = $unicornPath;
                break;
            }
            $path = dirname($path);
        }
    }

    public function injectRepoList(RepositoryManager $rm, bool $preferDist = false)
    {
        if (empty($this->config)) {
            return;
        }

        $existsUrlSet = [];
        foreach ($rm->getRepositories() as $repo) {
            if ($repo instanceof FilterRepository) {
                $repo = $repo->getRepository();
            }
            if ($repo instanceof ConfigurableRepositoryInterface) {
                $cfg = $repo->getRepoConfig();
                $existsUrlSet[$cfg['url']] = true;
            }
        }
        $conflicts = $this->config['conflict'] ?? [];
        $options = $this->config['extra']['options'] ?? [];
        if (!$preferDist && isset($options['symlink']) && false == $options['symlink']) {
            $preferDist = true;
        }
        LocalPathRepository::setUp($conflicts, $options['reference'] ?? null);
        $rm->setRepositoryClass('path', LocalPathRepository::class);

        foreach ($this->reposFromFile($this->config) as $cfg) {
            if (isset($existsUrlSet[$cfg['url']])) {
                continue;
            }
            $existsUrlSet[$cfg['url']] = true;
            if ($preferDist && $cfg['type'] == 'path') {
                $cfg['options'] = ['symlink' => false];
            }
//          if ($cfg['url'] == '.') {
//              $cfg['url'] = realpath($cfg['url']);
//          }
            $rm->prependRepository(
                $rm->createRepository($cfg['type'], $cfg)
            );
        }
    }

    private function reposFromFile(array $pkgInfo): array
    {
        if (!isset($pkgInfo['repositories'])) {
            return [];
        }
        $repoList = [];
        $dirPath = dirname($pkgInfo['path']);
        $pathUtil = new PathUtil($dirPath);
        $repoList[] = [
            'type' => 'path',
            'url' => $this->relative($dirPath)
        ];
        foreach ($pkgInfo['repositories'] as $repo) {
            if ($repo['type'] == 'path' && PathUtil::isRelative($repo['url'])) {
                $repo['url'] = $this->relative(
                    $pathUtil->absolute($repo['url'])
                );
            }
            $repoList[] = $repo;
        }

        return array_reverse($repoList);
    }

    private function relative(string $url): string
    {
        $path = $url;
        $pos = strpos($url, '/*');
        if ($pos) {
            $path = substr($url, 0, $pos);
        }
        $path = $this->pathUtil->relative($path);
        if (substr($path, 0, 1) !== '.') {
            $path = './' . $path;
        }
        if ($pos) {
            $path .= substr($url, $pos);
        }

        return $path;
    }

    public function config(): array
    {
        return $this->config;
    }
}
