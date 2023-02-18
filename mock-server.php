<?php

require __DIR__ . '/vendor/autoload.php';

(new lav45\MockServer\Server())
    ->setHost(getenv('HOST'))
    ->setPort(getenv('PORT'))
    ->setMocksPath(getenv('MOCKS_PATH'))
    ->setLocale(getenv('LOCALE'))
    ->start();
