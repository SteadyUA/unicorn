<?php

namespace SteadyUa\Unicorn\Command;

use Composer\Composer;
use Composer\Installer\MetapackageInstaller;
use Composer\Package\PackageInterface;
use SteadyUa\Unicorn\Provider;

class ShowCommand extends \Composer\Command\ShowCommand
{
    private Provider $provider;
    private Composer $composer;

    public function __construct(Provider $provider)
    {
        $this->provider = $provider;
        parent::__construct();
    }

    protected function configure()
    {
        parent::configure();
        $this->setName('uni:show');
        $this->setHelp($this->getHelp() . <<<EOT


<info>composer uni:show -s -P</info>   show monorepo root path
<info>composer uni:show -D</info>      show local packages description
<info>composer uni:show -D -P</info>   show local packages path

EOT);
    }

    public function tryComposer(bool $disablePlugins = null, bool $disableScripts = null): Composer
    {
        if (!isset($this->composer)) {
            $this->composer = $this->provider->uniComposer();
            $im = $this->composer->getInstallationManager();
            $GLOBALS['uni_path'] = $this->provider->getDir();
            $im->addInstaller(
                new class($this->getIO()) extends MetapackageInstaller {
                    public function getInstallPath(PackageInterface $package)
                    {
                        return $GLOBALS['uni_path'];
                    }
                }
            );
        }

        return $this->composer;
    }

    public function requireComposer(bool $disablePlugins = null, bool $disableScripts = null): Composer
    {
        return $this->tryComposer($disablePlugins, $disableScripts);
    }
}
