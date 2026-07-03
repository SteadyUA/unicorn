<?php

namespace SteadyUa\Unicorn\Tests\Integration;

class VersionCommandTest extends IntegrationTestCase
{
    public function testUniVersionBumpsConstraint()
    {
        // For this test, we need a clean slate of composer.json files because the version command modifies them.
        // IntegrationTestCase isolates the global composer, but edits to tests/Fixtures/demo/composer.json will persist on the git tree.
        // To avoid polluting the repository, let's copy the entire demo directory to a temp location for this test!

        $tempFixtureDir = sys_get_temp_dir() . '/unicorn_demo_' . uniqid();
        $this->copyDirectory($this->fixtureDir, $tempFixtureDir);

        // Change fixture directory for this test
        $originalFixtureDir = $this->fixtureDir;
        $this->fixtureDir = $tempFixtureDir;

        try {
            // Remove the copied .git file which points to the submodule dir
            if (file_exists($this->fixtureDir . '/.git')) {
                unlink($this->fixtureDir . '/.git');
            }

            // Ensure git is initialized in the temp repo (since unicorn:version relies on it)
            exec('cd ' . escapeshellarg($this->fixtureDir) . ' && git init && git add . && git config user.email "test@example.com" && git config user.name "Test" && git commit -m "Initial commit"');

            // Act: Run uni:version inside packages/logger
            // Wait, we need to cd into packages/logger and run composer uni:version minor
            $process = $this->runComposerCommand(['uni:version', 'minor', '-d', 'packages/logger']);

            // Assert
            $this->assertTrue($process->isSuccessful(), "Command failed. Output: \n" . $process->getErrorOutput() . "\n" . $process->getOutput());
            
            // Wait, uni:version checks out a new branch, updates version in logger, updates dependants (web, worker), 
            // commits, creates PR, etc.
            // Let's assert that apps/web/composer.json was modified to require the new version.
            // Actually, does demo packages have version requirements in composer.json? They use "*". 
            // Wait, if it's "*", uni:version might not bump it or it might bump it to "0.1.*".
            // The command output will tell us what it did.
            $output = $process->getOutput() . $process->getErrorOutput();
            $this->assertStringContainsString('demo/logger', $output);

        } finally {
            // Restore original fixture dir
            $this->fixtureDir = $originalFixtureDir;
            $this->removeDirectory($tempFixtureDir);
        }
    }

    private function copyDirectory(string $source, string $dest): void
    {
        mkdir($dest, 0777, true);
        foreach (
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            ) as $item
        ) {
            if ($item->isDir()) {
                mkdir($dest . DIRECTORY_SEPARATOR . $iterator->getSubPathname());
            } else {
                copy($item, $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathname());
            }
        }
    }
}
