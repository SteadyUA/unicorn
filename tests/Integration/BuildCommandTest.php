<?php

namespace SteadyUa\Unicorn\Tests\Integration;

class BuildCommandTest extends IntegrationTestCase
{
    public function testUniBuildCreatesPhysicalFiles()
    {
        // First ensure it's installed
        $this->runComposerCommand(['uni:install']);

        // Define build path
        $buildDir = $this->fixtureDir . '/build/web';
        if (is_dir($buildDir)) {
            $this->removeDirectory($buildDir);
        }

        // Act: Run uni:build
        $process = $this->runComposerCommand(['uni:build', 'demo/web', 'build/web']);

        // Assert
        $this->assertTrue($process->isSuccessful(), "Command failed. Output: \n" . $process->getErrorOutput() . "\n" . $process->getOutput());
        
        // Check that build directory exists
        $this->assertDirectoryExists($buildDir, "Output was:\n" . $process->getOutput() . "\n" . $process->getErrorOutput());
        
        // Assert logger and database are copied (not symlinked) in the build vendor
        $buildVendorLoggerPath = $buildDir . '/vendor/demo/logger';
        $this->assertDirectoryExists($buildVendorLoggerPath);
        $this->assertFalse(is_link($buildVendorLoggerPath), 'Logger should NOT be a symlink in a build');
        
        $buildVendorDatabasePath = $buildDir . '/vendor/demo/database';
        $this->assertDirectoryExists($buildVendorDatabasePath);
        $this->assertFalse(is_link($buildVendorDatabasePath), 'Database should NOT be a symlink in a build');
        
        // Cleanup
        $this->removeDirectory($this->fixtureDir . '/build');
    }
}
