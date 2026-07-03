<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use Symfony\Component\Process\Process;

$composerHome = dirname(__DIR__) . '/.phpunit.composer.cache';

// Only create and run composer global update if it doesn't exist
// If you need to refresh it, you can delete the .phpunit.composer.cache folder
if (!is_dir($composerHome)) {
    echo "Initializing global COMPOSER_HOME for tests...\n";
    mkdir($composerHome, 0777, true);

    $pluginRootDir = dirname(__DIR__);

    $globalComposerJson = [
        'repositories' => [
            [
                'type' => 'path',
                'url' => $pluginRootDir,
                'options' => [
                    'symlink' => true
                ]
            ]
        ],
        'require' => [
            'steady-ua/unicorn' => '*@dev'
        ],
        'config' => [
            'allow-plugins' => [
                'steady-ua/unicorn' => true
            ]
        ]
    ];

    file_put_contents(
        $composerHome . '/composer.json', 
        json_encode($globalComposerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );

    $process = new Process(['composer', 'global', 'update']);
    $process->setEnv(['COMPOSER_HOME' => $composerHome]);
    $process->run();

    if (!$process->isSuccessful()) {
        echo "Failed to setup global composer home: " . $process->getErrorOutput() . "\n";
        exit(1);
    }
}

putenv('UNICORN_TEST_COMPOSER_HOME=' . $composerHome);
$_ENV['UNICORN_TEST_COMPOSER_HOME'] = $composerHome;
