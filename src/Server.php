<?php

namespace lav45\MockServer;

use Amp\ByteStream;
use Amp\Http\Server\DefaultErrorHandler;
use Amp\Http\Server\Router;
use Amp\Http\Server\SocketHttpServer;
use Amp\Log\ConsoleFormatter;
use Amp\Log\StreamHandler;
use Amp\Socket;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use function Amp\trapSignal;

/**
 * Class MockServer
 * @package lav45\MockServer
 */
class Server
{
    /** @var string */
    private $host;
    /** @var int */
    private $port;
    /** @var \Generator|array */
    private $mocks;

    public function start()
    {
        $logHandler = new StreamHandler(ByteStream\getStdout());
        $logHandler->pushProcessor(new PsrLogMessageProcessor());
        $logHandler->setFormatter(new ConsoleFormatter());

        $logger = new Logger('mock-server');
        $logger->pushHandler($logHandler);

        $server = new SocketHttpServer($logger);
        $server->expose(new Socket\InternetAddress($this->host, $this->port));

        $errorHandler = new DefaultErrorHandler();
        $router = new Router($server, $errorHandler);

        foreach ($this->mocks as $item) {
            $mock = new Mock($item);
            $mockRequest = $mock->getRequest();
            $responseHandler = new MockResponseHandler($mock->getResponse());

            $router->addRoute(
                $mockRequest->method,
                $mockRequest->path,
                $responseHandler,
            );
        }

        $server->start($router, $errorHandler);
        $logger->info(sprintf("Received signal %d, stopping HTTP server", trapSignal([SIGINT, SIGTERM])));
        $server->stop();
    }

    /**
     * @param string $host
     * @return static
     */
    public function setHost(string $host)
    {
        $this->host = $host;
        return $this;
    }

    /**
     * @param int $port
     * @return static
     */
    public function setPort(int $port)
    {
        $this->port = $port;
        return $this;
    }

    /**
     * @param \Generator|array $items
     * @return static
     */
    public function setMocks($items)
    {
        $this->mocks = $items;
        return $this;
    }
}
