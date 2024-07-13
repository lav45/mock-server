<?php declare(strict_types=1);

require(__DIR__ . '/../vendor/autoload.php');

// Fake proxy server
Amp\async(function () {
    (new Lav45\MockServer\Test\Functional\Server(
        port: 8000,
        logLevel: 'error',
    ))->start();
});

// Test mock server
Amp\async(function () {
    (new Lav45\MockServer\Server(
        port: 80,
        mocksPath: '/app/test/Functional/mocks',
        logLevel: 'error',
    ))->start();
});
