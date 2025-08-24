<?php declare(strict_types=1);

require(__DIR__ . '/../vendor/autoload.php');

// Fake proxy server
Amp\async(static function () {
    new Lav45\MockServer\Test\Functional\Server(
        port: 8000,
        logLevel: 'error',
    )->start();
});

// Test mock server
Amp\async(static function () {
    $config = new Lav45\MockServer\Config()
        ->port(80)
        ->mocks('/app/test/Functional/mocks')
        ->log('error')
        ->fileWatch(0);

    new Lav45\MockServer\Server($config)->start();
});
