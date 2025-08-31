<?php declare(strict_types=1);

use Monolog\Level;

require(__DIR__ . '/../vendor/autoload.php');

Amp\async(static function () {
    // Fake proxy server
    $logger = Lav45\MockServer\LoggerFactory::create('logger', Level::Error);

    new Lav45\MockServer\Test\Functional\Server($logger)->start();

    // Test mock server
    $config = new Lav45\MockServer\Config()
        ->port(80)
        ->mocks('/app/test/Functional/mocks')
        ->fileWatch(0);

    new Lav45\MockServer\Server($config, $logger)->start();
});
