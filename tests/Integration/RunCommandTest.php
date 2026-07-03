<?php

namespace SteadyUa\Unicorn\Tests\Integration;

class RunCommandTest extends IntegrationTestCase
{
    public function testUniRunExecutesRecursively()
    {
        // First ensure it's installed
        $this->runComposerCommand(['uni:install']);

        // Act: Run uni:run test inside packages/database
        // This should run the test script for its dependents (demo/web)
        $process = $this->runComposerCommand(['uni:run', 'test', '-d', 'packages/database']);

        // Assert
        $this->assertTrue($process->isSuccessful(), "Command failed. Output: \n" . $process->getErrorOutput() . "\n" . $process->getOutput());
        
        // Assert the script ran for web
        $output = $process->getOutput() . $process->getErrorOutput();
        $this->assertStringContainsString('demo/web', $output);
        $this->assertStringContainsString('Ok', $output);
    }
}
