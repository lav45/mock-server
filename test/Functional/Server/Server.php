<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Functional\Server;

use Amp\Http\Server\DefaultErrorHandler;
use Amp\Http\Server\Driver\SocketClientFactory;
use Amp\Http\Server\HttpServer;
use Amp\Http\Server\SocketHttpServer;
use Amp\Socket;
use Lav45\MockServer\Test\Functional\Server\Component\Storage;
use Psr\Log\LoggerInterface;

readonly class Server
{
    public function __construct(
        private LoggerInterface $logger,
        private int             $port = 8000,
    ) {}

    public function start(): HttpServer
    {
        $serverSocketFactory = new Socket\ResourceServerSocketFactory();
        $clientFactory = new SocketClientFactory($this->logger);
        $server = new SocketHttpServer($this->logger, $serverSocketFactory, $clientFactory);
        $server->expose(new Socket\InternetAddress('0.0.0.0', $this->port));

        $storage = new Storage();
        $requestHandler = new RequestHandler($storage);
        $errorHandler = new DefaultErrorHandler();
        $server->start($requestHandler, $errorHandler);
        return $server;
    }
}
