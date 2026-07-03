<?php

namespace SteadyUa\Unicorn\Tests\Integration;

class NamespaceCommandTest extends IntegrationTestCase
{
    public function testUniNamespaceFindsPackage()
    {
        $process = $this->runComposerCommand(['uni:namespace', '*Logger*']);
        $output = $process->getOutput();

        $this->assertStringContainsString('Demo\Logger\\', $output);
        $this->assertStringContainsString('demo/logger', $output);
        $this->assertStringContainsString('packages/logger', $output);
    }

    public function testUniNamespaceNotFound()
    {
        $process = $this->runComposerCommand(['uni:namespace', '*NonExistent*']);
        $output = $process->getOutput();

        $this->assertStringContainsString('Nothing found', $output);
    }
}
