<?php

namespace lav45\MockServer;

use Amp;
use Amp\ByteStream;
use Amp\Http\Server\DefaultErrorHandler;
use Amp\Http\Server\SocketHttpServer;
use Amp\Log\ConsoleFormatter;
use Amp\Log\StreamHandler;
use Amp\Socket;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;

/**
 * Class Server
 * @package lav45\MockServer
 */
class Server
{
    /** @var int */
    private int $port = 8080;
    /** @var string */
    private string $mocksPath = '/app/mocks';

    public function start()
    {
        $logHandler = new StreamHandler(ByteStream\getStdout());
        $logHandler->pushProcessor(new PsrLogMessageProcessor());
        $logHandler->setFormatter(new ConsoleFormatter(
            format: "[%datetime%] %level_name%:\t%message%\r\n",
            dateFormat: 'd.m.Y H:i:s'
        ));

        $logger = new Logger('mock-server');
        $logger->pushHandler($logHandler);

        $server = new SocketHttpServer($logger);
        $server->expose(new Socket\InternetAddress('0.0.0.0', $this->port));

        $errorHandler = new DefaultErrorHandler();
        $router = new Router($this->mocksPath, $errorHandler);

        $server->start($router, $errorHandler);
        $logger->info(sprintf("Received signal %d, stopping HTTP server", Amp\trapSignal([SIGINT, SIGTERM])));
        $server->stop();
    }

    /**
     * @param int $port
     * @return static
     */
    public function setPort(int $port)
    {
        if ($port) {
            $this->port = $port;
        }
        return $this;
    }

    /**
     * @param string $path
     * @return static
     */
    public function setMocksPath(string $path)
    {
        if ($path) {
            $this->mocksPath = $path;
        }
        return $this;
    }
}
