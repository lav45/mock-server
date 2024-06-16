<?php declare(strict_types=1);

namespace lav45\MockServer\test\functional\server;

use Amp;
use Amp\ByteStream;
use Amp\Http\Server\DefaultErrorHandler;
use Amp\Http\Server\Driver\SocketClientFactory;
use Amp\Http\Server\ErrorHandler;
use Amp\Http\Server\HttpServer;
use Amp\Http\Server\SocketHttpServer;
use Amp\Log\ConsoleFormatter;
use Amp\Log\StreamHandler;
use Amp\Socket;
use lav45\MockServer\test\functional\server\components\Storage;
use Monolog\Level;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use Psr\Log\LoggerInterface;

readonly class Server
{
    public function __construct(
        private string $host = '0.0.0.0',
        private int    $port = 8000,
        private string $logLevel = 'info',
    ) {}

    public function start(): void
    {
        $logHandler = $this->getLogHandler();
        $logger = $this->getLogger($logHandler);
        $server = $this->getServer($logger);
        $errorHandler = $this->getErrorHandler();

        $storage = new Storage();
        $requestHandler = new RequestHandler($storage);

        $server->start($requestHandler, $errorHandler);
        $logger->info(\sprintf("Received signal %d, stopping HTTP server", Amp\trapSignal([SIGINT, SIGTERM])));
        $server->stop();
    }

    protected function getErrorHandler(): ErrorHandler
    {
        return new DefaultErrorHandler();
    }

    protected function getServer(LoggerInterface $logger): HttpServer
    {
        $serverSocketFactory = new Socket\ResourceServerSocketFactory();
        $clientFactory = new SocketClientFactory($logger);
        $server = new SocketHttpServer($logger, $serverSocketFactory, $clientFactory);
        $server->expose(new Socket\InternetAddress($this->host, $this->port));
        return $server;
    }

    protected function getLogger(StreamHandler $handler, string $name = 'listen-server'): LoggerInterface
    {
        return (new Logger($name))->pushHandler($handler);
    }

    protected function getLogHandler(): StreamHandler
    {
        $handler = new StreamHandler(ByteStream\getStdout());
        $handler->setLevel(Level::fromName($this->logLevel));
        $handler->pushProcessor(new PsrLogMessageProcessor());
        $handler->setFormatter(new ConsoleFormatter());
        return $handler;
    }
}
