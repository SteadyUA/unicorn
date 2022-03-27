<?php

namespace SteadyUa\Unicorn;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\Capability\CommandProvider;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;
use GlobIterator;

class Plugin implements PluginInterface, Capable, CommandProvider
{
    /** @var Provider */
    private static $provider;

    public function activate(Composer $composer, IOInterface $io)
    {
        self::$provider = new Provider(getcwd());

        $task = $GLOBALS['argv'][1] ?? 'help';
        if (in_array($task, ['help', 'run', 'run-script', 'exec', 'config', 'about', 'uni:source'])) {
            return;
        }

        $dm = $composer->getDownloadManager();
        $dm->setDownloader('path', new LocalPathDownloader($dm->getDownloader('path'), $io));

        self::$provider->injectRepoList(
            $composer->getRepositoryManager(),
            in_array('--prefer-dist', $GLOBALS['argv'])
        );
    }

    public function getCommands()
    {
        return [
            new SourceCommand(self::$provider),
            new SuggestCommand(),
            new ProjectCommand(),
            new ListCommand(),
        ];
    }

    public function deactivate(Composer $composer, IOInterface $io)
    {
    }

    public function uninstall(Composer $composer, IOInterface $io)
    {
        $it = new GlobIterator($composer->getConfig()->get('home') . '/repo-*');
        foreach ($it as $file) {
            unlink($file->getPathname());
        }
    }

    public function getCapabilities()
    {
        return [
            CommandProvider::class => self::class
        ];
    }
}
