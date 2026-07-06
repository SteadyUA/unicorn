<?php

namespace SteadyUa\Unicorn\Tests\Integration;

use Symfony\Component\Process\Process;

class SplitCommandTest extends IntegrationTestCase
{
    private string $tempDir;
    private string $remoteDir;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->tempDir = sys_get_temp_dir() . '/unicorn_split_test_' . uniqid();
        $this->remoteDir = sys_get_temp_dir() . '/unicorn_logger_remote_' . uniqid() . '.git';

        $this->removeDirectory($this->tempDir);
        $this->removeDirectory($this->remoteDir);

        mkdir($this->tempDir, 0777, true);
        mkdir($this->remoteDir, 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
        $this->removeDirectory($this->remoteDir);
        parent::tearDown();
    }

    public function testSplitCommandSuccessfullySplitsAndTags()
    {
        // 1. Copy fixture to temp dir
        $process = new Process(['cp', '-r', $this->fixtureDir, $this->tempDir . '/fixture']);
        $process->run();
        $this->assertTrue($process->isSuccessful(), 'Failed to copy fixture');
        
        // Let's use the copied fixture dir as our working dir
        $this->tempDir = $this->tempDir . '/fixture';
        if (file_exists($this->tempDir . '/.git')) {
            unlink($this->tempDir . '/.git');
        }

        // 2. Setup bare remote
        $process = new Process(['git', 'init', '--bare'], $this->remoteDir);
        $process->run();
        $this->assertTrue($process->isSuccessful(), 'Failed to init bare remote');

        // 3. Configure package config to push to the local bare remote
        $loggerComposer = $this->tempDir . '/packages/logger/composer.json';
        $json = json_decode(file_get_contents($loggerComposer), true);
        $json['extra']['uni-split'] = [
            'remote-pattern' => 'file://' . $this->remoteDir
        ];
        file_put_contents($loggerComposer, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        // 4. Init git in temp dir and commit
        $commands = [
            ['git', 'init'],
            ['git', 'config', 'user.email', 'test@example.com'],
            ['git', 'config', 'user.name', 'Test User'],
            ['git', 'add', '.'],
            ['git', 'commit', '-m', 'Initial commit']
        ];
        foreach ($commands as $cmd) {
            $process = new Process($cmd, $this->tempDir);
            $process->run();
            $this->assertTrue($process->isSuccessful(), 'Failed git setup: ' . $process->getErrorOutput());
        }

        // 5. Run composer uni:split
        $process = new Process(['composer', 'uni:split', '-v'], $this->tempDir);
        $process->setEnv(['COMPOSER_HOME' => $this->composerHome]);
        $process->run();
        
        $this->assertTrue($process->isSuccessful(), 'SplitCommand failed: ' . $process->getErrorOutput());
        $output = $process->getOutput() . $process->getErrorOutput();
        $this->assertStringContainsString('Splitting demo/logger', $output);
        $this->assertStringContainsString('Pushing tag v1.0.0', $output);
        $this->assertStringContainsString('Success!', $output);

        // 6. Verify remote repository received the branch and tag
        $process = new Process(['git', 'branch'], $this->remoteDir);
        $process->run();
        $this->assertStringContainsString('main', $process->getOutput());

        $process = new Process(['git', 'tag'], $this->remoteDir);
        $process->run();
        $this->assertStringContainsString('v1.0.0', $process->getOutput());
    }
}
