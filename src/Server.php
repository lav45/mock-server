<?php declare(strict_types=1);

namespace Lav45\MockServer;

use Amp;
use Amp\ByteStream;
use Amp\Http\Server\DefaultErrorHandler;
use Amp\Http\Server\Driver\SocketClientFactory;
use Amp\Http\Server\HttpServer;
use Amp\Http\Server\SocketHttpServer;
use Amp\Log\ConsoleFormatter;
use Amp\Log\StreamHandler;
use Amp\Socket;
use Faker\Factory as FakerFactory;
use Lav45\MockServer\Infrastructure\Service\HttpClientFactory;
use Monolog\Level;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use Psr\Log\LoggerInterface;

final readonly class Server
{
    public function __construct(
        private string $host = '0.0.0.0',
        private int    $port = 8080,
        private string $mocksPath = '/app/mocks',
        private string $locale = 'en_US',
        private string $logLevel = 'info',
        private float  $fileWatchTimeout = 0.2,
    ) {}

    public function start(): void
    {
        $logger = $this->getLogger();

        $faker = FakerFactory::create($this->locale);
        $httpClient = HttpClientFactory::create($logger);
        $requestFactory = new RequestFactory($faker, $httpClient, $logger);
        $watcher = $this->runWatcher($logger, $requestFactory);

        $errorHandler = new DefaultErrorHandler();
        $reactor = new Reactor(
            errorHandler: $errorHandler,
            watcher: $watcher,
        );

        $server = $this->getServer($logger);
        $server->expose(new Socket\InternetAddress($this->host, $this->port));
        $server->start($reactor, $errorHandler);
        $logger->info(\sprintf("Received signal %d, stopping HTTP server", Amp\trapSignal([SIGINT, SIGTERM])));
        $server->stop(); // @codeCoverageIgnore
    }

    private function runWatcher(LoggerInterface $logger, RequestFactory $requestFactory): Watcher
    {
        $watcher = new Watcher(
            requestFactory: $requestFactory,
            watchDir: $this->mocksPath,
            logger: $logger,
        );
        $watcher->init();

        if ($this->fileWatchTimeout > 0) {
            Amp\async(fn() => $watcher->run($this->fileWatchTimeout));
        }
        return $watcher;
    }

    private function getServer(LoggerInterface $logger): HttpServer
    {
        $serverSocketFactory = new Socket\ResourceServerSocketFactory();
        $clientFactory = new SocketClientFactory($logger);
        return new SocketHttpServer($logger, $serverSocketFactory, $clientFactory);
    }

    private function getLogger(): LoggerInterface
    {
        $handler = new StreamHandler(ByteStream\getStdout());
        $handler->setLevel(Level::fromName($this->logLevel));
        $handler->pushProcessor(new PsrLogMessageProcessor());
        $handler->setFormatter(new ConsoleFormatter(
            format: "[%datetime%]\t%level_name%\t%message%\t%context%\n",
            dateFormat: 'd.m.Y H:i:s.v',
            allowInlineLineBreaks: true,
            ignoreEmptyContextAndExtra: true,
        ));

        return (new Logger('mock-server'))->pushHandler($handler);
    }
}
