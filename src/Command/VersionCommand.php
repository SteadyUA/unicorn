<?php

namespace SteadyUa\Unicorn\Command;

use Composer\Command\BaseCommand;
use RuntimeException;
use SteadyUa\Unicorn\Cmd\AbstractCmd;
use SteadyUa\Unicorn\Cmd\InstallCmd;
use SteadyUa\Unicorn\Cmd\RunScriptsCmd;
use SteadyUa\Unicorn\Cmd\VerBackupCmd;
use SteadyUa\Unicorn\Cmd\VerUpdateFilesCmd;
use SteadyUa\Unicorn\Provider;
use SteadyUa\Unicorn\Utils;
use SteadyUa\Unicorn\Version;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class VersionCommand extends BaseCommand
{
    /** @var Provider  */
    private $provider;

    public const TYPE_PATCH = 'patch';
    public const TYPE_MINOR = 'minor';
    public const TYPE_MAJOR = 'major';

    public function __construct(Provider $provider)
    {
        $this->provider = $provider;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('uni:version')
            ->setDescription('Bump version of package.')
            ->addArgument('type', InputArgument::OPTIONAL, 'major | minor | patch', 'patch')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $package = $this->provider->composer()->getPackage();
        $localRepo = $this->provider->localRepo();
        if ($package->getName() == '__root__') {
            $output->writeln('Could not find a composer.json file in ' . getcwd());
            return self::FAILURE;
        }
        $package = $localRepo->findPackage($package->getName(), '*');
        if (!$package) {
            $output->writeln('Only available for local packages.');
            return self::FAILURE;
        }

        $io = $this->getIO();
        $utils = new Utils($io, $output);
        $version = new Version($package->getVersion());
        switch ($input->getArgument('type')) {
            case self::TYPE_PATCH:
                $newVersion = $version->patch();
                break;
            case self::TYPE_MINOR:
                $newVersion = $version->minor();
                break;
            case self::TYPE_MAJOR:
                $newVersion = $version->major();
                break;
            default:
                throw new RuntimeException('Unknown type: ' . $input->getArgument('type'));
        }

        $question = "Bump the package {$package->getName()} version"
            . ' from ' . $version
            . ' to ' . $newVersion;
        $res = $io->askConfirmation("<question> $question? </question>", true);
        if (!$res) {
            return false;
        }

        $blocked = $this->provider->getProhibits($package, $newVersion);
        $install = $this->provider->getDepends($package, true);
        $install = [$package->getName() => $package] + $install;

        $backupCmd = new VerBackupCmd($package, $blocked);
        $updateCmd = new VerUpdateFilesCmd(
            $package,
            $blocked,
            $newVersion
        );
        $installCmd = new InstallCmd($utils, $install);

        $cmd = AbstractCmd::emptyCmd();
        $cmd->setNext($backupCmd)
            ->setNext($updateCmd)
            ->setNext($installCmd)
        ;
        $scripts = $this->provider->getScripts()['post-install'] ?? [];
        if ($scripts) {
            $installCmd->setNext(
                new RunScriptsCmd($utils, $scripts, $install)
            );
        }

        return AbstractCmd::runCmd($cmd, $io);
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
    }
}
