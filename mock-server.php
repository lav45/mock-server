<?php

require __DIR__ . '/vendor/autoload.php';

(new Lav45\MockServer\Server(
    host: getenv('HOST') ?: '0.0.0.0',
    port: (int)(getenv('PORT') ?: 8080),
    mocksPath: getenv('MOCKS_PATH') ?: '/app/mocks',
    locale: getenv('LOCALE') ?: 'en_US',
    logLevel: getenv('LOG_LEVEL') ?: 'info',
    fileWatchTimeout: (float)(getenv('FILE_WATCH_TIMEOUT') ?: 0.2)
))->start();
