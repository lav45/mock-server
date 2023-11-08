<?php declare(strict_types=1);

namespace lav45\MockServer;

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
use Faker\Factory;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;

class Server
{
    public function __construct(
        private readonly string $host = '0.0.0.0',
        private readonly int    $port = 8080,
        private readonly string $mocksPath = '/app/mocks',
        private readonly string $locale = 'en_US',
    )
    {
    }

    public function start(): void
    {
        $logHandler = $this->getLogHandler();
        $logger = $this->getLogger($logHandler);
        $server = $this->getServer($logger);
        $errorHandler = $this->getErrorHandler();
        $factory = $this->getFactory();

        $router = new Reactor(
            mocksPath: $this->mocksPath,
            errorHandler: $errorHandler,
            faker: $factory,
            logger: $logger,
        );

        $server->start($router, $errorHandler);
        $logger->info(sprintf("Received signal %d, stopping HTTP server", Amp\trapSignal([SIGINT, SIGTERM])));
        $server->stop();
    }

    protected function getFactory(): FakerParser
    {
        return new FakerParser(Factory::create($this->locale));
    }

    protected function getErrorHandler(): ErrorHandler
    {
        return new DefaultErrorHandler();
    }

    protected function getServer(Logger $logger): HttpServer
    {
        $serverSocketFactory = new Socket\ResourceServerSocketFactory();
        $clientFactory = new SocketClientFactory($logger);
        $server = new SocketHttpServer($logger, $serverSocketFactory, $clientFactory);
        $server->expose(new Socket\InternetAddress($this->host, $this->port));
        return $server;
    }

    protected function getLogger(StreamHandler $handler, string $name = 'mock-server'): Logger
    {
        return (new Logger($name))->pushHandler($handler);
    }

    protected function getLogHandler(): StreamHandler
    {
        return (new StreamHandler(ByteStream\getStdout()))
            ->pushProcessor(new PsrLogMessageProcessor())
            ->setFormatter(new ConsoleFormatter(
                format: "[%datetime%]\t%level_name%\t%message%\t%context%\n",
                dateFormat: 'd.m.Y H:i:s.v',
                allowInlineLineBreaks: true,
                ignoreEmptyContextAndExtra: true
            ));
    }
}
