<?php

namespace SteadyUa\Unicorn\Tests\Integration;

class InstallCommandTest extends IntegrationTestCase
{
    public function testUniInstallCreatesSymlinks()
    {
        // Act
        $process = $this->runComposerCommand(['uni:install']);

        // Assert
        $this->assertTrue($process->isSuccessful(), "Command failed. Output: \n" . $process->getErrorOutput() . "\n" . $process->getOutput());
        
        // Assert vendor exists
        $this->assertDirectoryExists($this->fixtureDir . '/vendor');
        
        // Assert logger and database symlinks exist in web app
        $webVendorLoggerPath = $this->fixtureDir . '/apps/web/vendor/demo/logger';
        $this->assertTrue(is_link($webVendorLoggerPath), 'Logger should be symlinked in web vendor');
        
        $webVendorDatabasePath = $this->fixtureDir . '/apps/web/vendor/demo/database';
        $this->assertTrue(is_link($webVendorDatabasePath), 'Database should be symlinked in web vendor');
    }
}
