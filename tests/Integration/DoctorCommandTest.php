<?php

namespace SteadyUa\Unicorn\Tests\Integration;

use Symfony\Component\Process\Process;

class DoctorCommandTest extends IntegrationTestCase
{
    public function testDoctorCommandInMonorepo()
    {
        // Add an orphaned package
        $orphanDir = $this->fixtureDir . '/packages/orphan';
        mkdir($orphanDir, 0777, true);
        file_put_contents($orphanDir . '/composer.json', json_encode([
            'name' => 'demo/orphan',
            'type' => 'library'
        ]));

        // Add a misplaced folder
        $misplacedDir = $this->fixtureDir . '/apps/misplaced';
        mkdir($misplacedDir, 0777, true);
        file_put_contents($misplacedDir . '/package.json', '{}'); // No composer.json

        // Add a non-glob matching folder
        $nonGlobDir = $this->fixtureDir . '/apps/web/deep-folder';
        mkdir($nonGlobDir, 0777, true);

        try {
            // First we need to install to make sure the orphan is recognized by uni_vendor
            // Because integration tests use the existing fixture which has no uni_vendor initially,
            // or wait, tests/Fixtures/demo has been initialized already if another test ran.
            // But we created demo/orphan just now, so we need to run uni:install to rebuild vendor.
            $installProcess = $this->runComposerCommand(['uni:install']);
            if (!$installProcess->isSuccessful()) {
                $this->fail("Failed to install demo/orphan: " . $installProcess->getErrorOutput());
            }

            $process = $this->runComposerCommand(['uni:doctor', '-vvv']);
            
            $output = $process->getOutput() . $process->getErrorOutput();
            
            $this->assertTrue($process->isSuccessful(), "Doctor command failed. Output:\n" . $output);

            $this->assertStringContainsString('unicorn.json is valid', $output);
            
            // Should find valid packages
            $this->assertStringContainsString('Found', $output);
            
            // Should find misplaced packages
            $this->assertStringContainsString('Invalid Package', $output);
            $this->assertStringContainsString('apps/misplaced', $output);
            
            // Should find orphans
            $this->assertStringContainsString('Orphan found', $output);
            $this->assertStringContainsString('demo/orphan', $output);
            
        } finally {
            // Clean up
            $this->removeDirectory($orphanDir);
            $this->removeDirectory($misplacedDir);
            $this->removeDirectory($nonGlobDir);
            // Run update to remove the orphan from uni_vendor
            $this->runComposerCommand(['uni:install']);
        }
    }

    public function testDoctorCommandOutsideMonorepo()
    {
        $tempDir = sys_get_temp_dir() . '/unicorn_test_outside_' . uniqid();
        mkdir($tempDir, 0777, true);

        try {
            $process = new Process(['composer', 'uni:doctor']);
            $process->setWorkingDirectory($tempDir);
            $process->setEnv(['COMPOSER_HOME' => $this->composerHome]);
            $process->run();
            
            $output = $process->getOutput() . $process->getErrorOutput();
            
            $this->assertTrue($process->isSuccessful(), "Doctor command failed outside monorepo.");
            $this->assertStringContainsString('Monorepo root not found', $output);
        } finally {
            $this->removeDirectory($tempDir);
        }
    }
}
