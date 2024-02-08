<?php

require(__DIR__ . '/../vendor/autoload.php');

// Fake proxy server
Amp\async(function () {
    (new lav45\MockServer\test\functional\server\Server(
        port: 8000,
        logLevel: 'error',
    ))->start();
});

// Test mock server
Amp\async(function () {
    (new lav45\MockServer\Server(
        port: 80,
        mocksPath: '/app/test/functional/mocks',
        logLevel: 'error',
    ))->start();
});