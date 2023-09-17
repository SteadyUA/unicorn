<?php

namespace SteadyUa\Unicorn\Command;

use Composer\Command\BaseCommand;
use SteadyUa\Unicorn\Action\AbstractAction;
use SteadyUa\Unicorn\Action\InstallAction;
use SteadyUa\Unicorn\Action\ReqBackupAction;
use SteadyUa\Unicorn\Action\ReqTryInstallAction;
use SteadyUa\Unicorn\Action\ReqUpdateFilesAction;
use SteadyUa\Unicorn\Action\RunScriptsAction;
use SteadyUa\Unicorn\Provider;
use SteadyUa\Unicorn\Utils;
use SteadyUa\Unicorn\Version;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateCommand extends BaseCommand
{
    private Provider $provider;

    public function __construct(Provider $provider)
    {
        $this->provider = $provider;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('uni:update')
            ->setDescription('Update required packages in all dependents.')
            ->setDefinition(
                [
                    new InputArgument(
                        'packages',
                        InputArgument::IS_ARRAY | InputArgument::REQUIRED,
                        'Package name can also also include a version constraint,'
                        . ' e.g. foo/bar or foo/bar:1.0.0 or foo/bar=1.0.0 or "foo/bar 1.0.0".'
                    ),
                ]
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = $this->getIO();

        $requirements = $this->normalizeRequirements($input->getArgument('packages'));

        $composer = $this->provider->uniComposer($io);
        $lockedRepository = $composer->getLocker()->getLockedRepository();
        $changes = [];
        $install = [];
        $localPackages = [];
        foreach ($requirements as $package) {
            $name = $package['name'];
            if ($name == 'self') {
                $name = $this->provider->composer()->getPackage()->getName();
                if ($name == '__root__') {
                    $output->writeln('Could not find a composer.json file in ' . getcwd());
                    return self::FAILURE;
                }
            }
            $lockedPkg = $lockedRepository->findPackage($name, '*');
            if (isset($lockedPkg)) {
                if ($lockedPkg->getDistType() == 'path') {
                    $localPackages[] = $name;
                }
                $depends = $this->provider->getDepends($lockedPkg);
            }
            if (empty($depends)) {
                $io->writeError('No dependencies found: ' . $name);
                // TODO: show recommendations
                return self::FAILURE;
            }
            if (isset($package['version'])) {
                $constraint = $package['version'];
            } else {
                $version = new Version($lockedPkg->getVersion());
                $constraint = $version->minorConstraint();
            }

            foreach ($depends as $depend) {
                if (!$depend) {
                    continue;
                }
                if (!isset($changes[$depend->getName()])) {
                    $changes[$depend->getName()] = [
                        'pkg' => $depend,
                        'req' => [],
                    ];
                    $install[$depend->getName()] = $depend;
                    $install += $this->provider->getDepends($depend, true);
                }
                $changes[$depend->getName()]['req'][$name] = $constraint;
            }
        }

        $utils = new Utils($io, $output);
        $backupCmd = new ReqBackupAction($changes);
        $updateCmd = new ReqUpdateFilesAction($changes);
        $tryInstallCmd = new ReqTryInstallAction($localPackages);
        $installCmd = new InstallAction($utils, $install);

        $scripts = $this->provider->getPostUpdateScripts();
        if ($scripts) {
            $installCmd->setNext(new RunScriptsAction($utils, $scripts, $install));
        }

        $cmd = AbstractAction::emptyCmd();
        $cmd->setNext($backupCmd)
            ->setNext($updateCmd)
            ->setNext($tryInstallCmd)
            ->setNext($installCmd)
        ;
        AbstractAction::runCmd($cmd, $io);

        return self::SUCCESS;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
    }
}
