<?php

namespace SteadyUa\Unicorn\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

abstract class IntegrationTestCase extends TestCase
{
    protected string $fixtureDir;
    protected string $composerHome;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Define paths
        $this->fixtureDir = dirname(__DIR__) . '/Fixtures/demo';
        $this->composerHome = getenv('UNICORN_TEST_COMPOSER_HOME');
        if (!$this->composerHome || !is_dir($this->composerHome)) {
            throw new \RuntimeException('UNICORN_TEST_COMPOSER_HOME is not set or invalid. Did you run tests through PHPUnit with bootstrap?');
        }

        // Clean up the fixture directory from previous test runs.
        // We do this in setUp so the files remain after the test for inspection.
        // NOTE: PHPUnit runs tests sequentially by default. Do not enable parallel execution 
        // for these tests to avoid race conditions on the fixture folder!
        $process = new Process(['git', 'clean', '-fdx']);
        $process->setWorkingDirectory($this->fixtureDir);
        $process->run();
        
        $process = new Process(['git', 'checkout', '.']);
        $process->setWorkingDirectory($this->fixtureDir);
        $process->run();
    }

    protected function runComposerCommand(array $command): Process
    {
        putenv('COMPOSER_HOME=' . $this->composerHome);
        $_ENV['COMPOSER_HOME'] = $this->composerHome;
        $_SERVER['COMPOSER_HOME'] = $this->composerHome;
        
        $process = new Process(array_merge(['composer'], $command));
        $process->setWorkingDirectory($this->fixtureDir);
        $process->setEnv(['COMPOSER_HOME' => $this->composerHome]);
        $process->run();
        
        putenv('COMPOSER_HOME'); // Unset
        unset($_ENV['COMPOSER_HOME']);
        unset($_SERVER['COMPOSER_HOME']);
        return $process;
    }

    protected function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = "$dir/$file";
            is_dir($path) && !is_link($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
