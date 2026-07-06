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
            // Act: Run uni:version inside packages/logger
            $process = $this->runComposerCommand(['uni:version', 'minor', '-d', 'packages/logger']);

            // Assert
            $this->assertTrue($process->isSuccessful(), "Command failed. Output: \n" . $process->getErrorOutput() . "\n" . $process->getOutput());
            
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
