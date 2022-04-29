<?php

namespace SteadyUa\Unicorn;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\Capability\CommandProvider;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;
use SteadyUa\Unicorn\Command\InstallCommand;
use SteadyUa\Unicorn\Command\ProhibitsCommand;
use SteadyUa\Unicorn\Command\UpdateCommand;
use SteadyUa\Unicorn\Command\RunCommand;
use SteadyUa\Unicorn\Command\ShowCommand;
use SteadyUa\Unicorn\Command\SuggestCommand;
use SteadyUa\Unicorn\Command\DependsCommand;
use SteadyUa\Unicorn\Command\VersionCommand;

class Plugin implements PluginInterface, Capable, CommandProvider
{
    /** @var Provider */
    private static $provider;

    public function activate(Composer $composer, IOInterface $io)
    {
        self::$provider = new Provider(getcwd(), $composer);
        if (empty(self::$provider->config())) {
            return;
        }

        $task = $GLOBALS['argv'][1] ?? 'help';
        if (in_array(
            $task,
            ['help', 'run', 'run-script', 'exec', 'config', 'about', 'uni:version', 'uni:update']
        )) {
            return;
        }

        self::$provider->setupUniComposer($io);
    }

    public function getCommands(): array
    {
        if (empty(self::$provider->config())) {
            return [];
        }
        return [
            new DependsCommand(self::$provider),
            new ProhibitsCommand(self::$provider),
            new ShowCommand(self::$provider),
            new SuggestCommand(self::$provider),
            new InstallCommand(self::$provider),
            new VersionCommand(self::$provider),
            new RunCommand(self::$provider),
            new UpdateCommand(self::$provider),
        ];
    }

    public function getCapabilities(): array
    {
        return [
            CommandProvider::class => self::class
        ];
    }

    public function deactivate(Composer $composer, IOInterface $io)
    {
    }

    public function uninstall(Composer $composer, IOInterface $io)
    {
    }
}
