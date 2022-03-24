<?php

namespace SteadyUa\Unicorn;

class PathUtil
{
    private $cwd;

    public function __construct(string $cwd)
    {
        $this->cwd = rtrim(realpath($cwd), '/');
    }

    public static function isAbsolute(string $path): bool
    {
        return substr($path, 0, 1) == '/';
    }

    public static function isRelative(string $path): bool
    {
        return substr($path, 0, 1) != '/';
    }

    public function absolute(string $relPath): string
    {
        if (self::isAbsolute($relPath)) {
            return $relPath;
        }
        $pathParts = explode('/', $relPath);
        $cwdParts = explode('/', $this->cwd);
        while (!empty($pathParts)) {
            $name = array_shift($pathParts);
            if ($name == '.') {
                continue;
            } elseif ($name == '..') {
                if (!empty($cwdParts)) {
                    array_pop($cwdParts);
                }
            } else {
                array_unshift($pathParts, $name);
                break;
            }
        }
        $path = implode('/', $cwdParts);
        if (!empty($pathParts)) {
            $path .= '/' . implode('/', $pathParts);
        }

        return $path;
    }

    public function relative(string $absPath): string
    {
        if (self::isRelative($absPath)) {
            return $absPath;
        }
        $pathParts = explode('/', realpath($absPath));
        $cwdParts = explode('/', $this->cwd);
        $pathCount = count($pathParts);
        $cwdCount = count($cwdParts);
        $matched = 0;
        for ($i = 0; $i < $pathCount; $i++) {
            if (!isset($cwdParts[$i]) || $pathParts[$i] != $cwdParts[$i]) {
                break;
            }
            $matched++;
        }
        $relPath = [];
        for ($n = $i; $n < $pathCount; $n++) {
            $relPath[] = $pathParts[$n];
        }
        if ($matched > 0 && $i < $cwdCount) {
            while ($i < $cwdCount) {
                array_unshift($relPath, '..');
                $i++;
            }
        }
        if (empty($relPath)) {
            return '.';
        }

        return implode('/', $relPath);
    }
}
