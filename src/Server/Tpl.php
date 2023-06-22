<?php

namespace SteadyUa\Unicorn\Server;

use Closure;

class Tpl
{
    private const TYPE_LAYOUT = 1;
    private const TYPE_EXTENDS = 2;

    private int $type;
    private array $blocks = [];
    private ?Closure $layoutBlock = null;
    private ?string $extend = null;
    private array $properties = [];
    private bool $loaded = false;
    private ?self $parent = null;

    private static self $current;
    /** @var mixed */
    private static $vars;

    private function __construct(int $type)
    {
        $this->type = $type;
    }

    public static function render(string $tplName, $vars = []): void
    {
        /** @var self $tpl */
        self::$current = $tpl = include $tplName;
        $tpl->loaded = true;
        while (self::TYPE_LAYOUT != $tpl->type) {
            /** @var self $ext */
            $ext = include $tpl->extend;
            $ext->loaded = true;
            $tpl->parent = $ext;
            $tpl = $ext;
        }

        self::$vars = $vars;
        self::call($tpl->layoutBlock);
    }

    private static function call(callable $closure): void
    {
        Closure::bind($closure, self::$current)(self::$vars);
    }

    public static function layout(callable $closure): self
    {
        $tpl = new self(self::TYPE_LAYOUT);
        $tpl->layoutBlock = $closure;

        return $tpl;
    }

    public static function extends(string $tplName): self
    {
        $tpl = new self(self::TYPE_EXTENDS);
        $tpl->extend = $tplName;

        return $tpl;
    }

    public function block(string $blockName, callable $closure = null): self
    {
        if (!$this->loaded) {
            $this->blocks[$blockName] = $closure ?? function () { };
        } elseif (!$this->show($blockName) && $closure) {
            self::call($closure);
        }

        return $this;
    }

    public function parent(string $blockName): bool
    {
        return $this->parent->show($blockName);
    }

    public function set(string $name, $value): self
    {
        $this->properties[$name] = $value;

        return $this;
    }

    public function get(string $name)
    {
        $c = $this;
        while (!isset($c->properties[$name]) && $c->parent) {
            $c = $c->parent;
        }

        return $c->properties[$name] ?? null;
    }

    public function show(string $blockName): bool
    {
        $c = $this;
        while (!isset($c->blocks[$blockName]) && $c->parent) {
            $c = $c->parent;
        }
        if (isset($c->blocks[$blockName])) {
            self::call($c->blocks[$blockName]);
            return true;
        }

        return false;
    }
}
