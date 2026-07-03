<?php

namespace SteadyUa\Unicorn\Tests\Integration;

class GraphCommandsTest extends IntegrationTestCase
{
    public function testUniWhyIdentifiesDependents()
    {
        $this->runComposerCommand(['uni:install']);
        
        // Act
        $process = $this->runComposerCommand(['uni:why', 'demo/logger']);

        // Assert
        $this->assertTrue($process->isSuccessful(), "Command failed. Output: \n" . $process->getErrorOutput() . "\n" . $process->getOutput());
        
        $output = $process->getOutput();
        // Logger is required by web and worker
        $this->assertStringContainsString('demo/web', $output);
        $this->assertStringContainsString('demo/worker', $output);
    }

    public function testUniShowListsMonorepoStructure()
    {
        $this->runComposerCommand(['uni:install']);

        // Act
        $process = $this->runComposerCommand(['uni:show']);

        // Assert
        $this->assertTrue($process->isSuccessful(), "Command failed. Output: \n" . $process->getErrorOutput() . "\n" . $process->getOutput());
        
        $output = $process->getOutput();
        $this->assertStringContainsString('demo/web', $output);
        $this->assertStringContainsString('demo/database', $output);
    }
}
