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
use Lav45\MockServer\Infrastructure\HttpClient\Factory as HttpClientFactory;
use Lav45\Watcher\Listener;
use Lav45\Watcher\Watcher as FileWatcher;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use Psr\Log\LoggerInterface;

final readonly class Server
{
    public function __construct(
        private Config $config,
    ) {}

    public function start(): void
    {
        $logger = $this->getLogger();

        $faker = FakerFactory::create($this->config->getLocale());
        $httpClient = HttpClientFactory::create($logger);
        $requestFactory = new RequestFactory($faker, $httpClient, $logger);
        $dispatcherFactory = new DispatcherFactory($requestFactory);
        $watcher = $this->runWatcher($logger, $dispatcherFactory);

        $errorHandler = new DefaultErrorHandler();
        $reactor = new Reactor(
            errorHandler: $errorHandler,
            watcher: $watcher,
        );

        $server = $this->getServer($logger);
        $server->expose(new Socket\InternetAddress('0.0.0.0', $this->config->getPort()));
        $server->start($reactor, $errorHandler);
        $logger->info(\sprintf("Received signal %d, stopping HTTP server", Amp\trapSignal([SIGINT, SIGTERM])));
        $server->stop(); // @codeCoverageIgnore
    }

    private function runWatcher(LoggerInterface $logger, DispatcherFactoryInterface $dispatcherFactory): WatcherInterface
    {
        $watcher = new Watcher(
            dispatcherFactory: $dispatcherFactory,
            watchDir: $this->config->getMocksPath(),
            logger: $logger,
        );
        $watcher->init();

        if ($timeout = $this->config->getFileWatchTimeout()) {
            // @codeCoverageIgnoreStart
            Amp\async(
                static fn() => $watcher->run(
                    new FileWatcher(new Listener()),
                    static fn() => Amp\delay($timeout),
                ),
            );
            // @codeCoverageIgnoreEnd
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
        $handler->setLevel($this->config->getLogLevel());
        $handler->pushProcessor(new PsrLogMessageProcessor());
        $handler->setFormatter(new ConsoleFormatter(
            format: "[%datetime%]\t%level_name%\t%message%\t%context%\n",
            dateFormat: 'd.m.Y H:i:s.v',
            allowInlineLineBreaks: true,
            ignoreEmptyContextAndExtra: true,
        ));

        return new Logger('mock-server')->pushHandler($handler);
    }
}
