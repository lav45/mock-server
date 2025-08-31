<?php declare(strict_types=1);

namespace Lav45\MockServer;

use Amp;
use Amp\Http\Server\DefaultErrorHandler;
use Amp\Http\Server\Driver\SocketClientFactory;
use Amp\Http\Server\HttpServer;
use Amp\Http\Server\SocketHttpServer;
use Amp\Socket;
use Faker\Factory as FakerFactory;
use Lav45\MockServer\Infrastructure\HttpClient\Factory as HttpClientFactory;
use Lav45\Watcher\Listener;
use Lav45\Watcher\Watcher as FileWatcher;
use Psr\Log\LoggerInterface;

final readonly class Server
{
    public function __construct(
        private Config          $config,
        private LoggerInterface $logger,
    ) {}

    public function start(): HttpServer
    {
        $faker = FakerFactory::create($this->config->getLocale());
        $httpClient = HttpClientFactory::create($this->logger);
        $requestFactory = new RequestFactory($faker, $httpClient, $this->logger);
        $dispatcherFactory = new DispatcherFactory($requestFactory);
        $watcher = $this->runWatcher($this->logger, $dispatcherFactory);

        $errorHandler = new DefaultErrorHandler();
        $reactor = new Reactor(
            errorHandler: $errorHandler,
            watcher: $watcher,
        );

        $serverSocketFactory = new Socket\ResourceServerSocketFactory();
        $clientFactory = new SocketClientFactory($this->logger);
        $server = new SocketHttpServer($this->logger, $serverSocketFactory, $clientFactory);
        $server->expose(new Socket\InternetAddress('0.0.0.0', $this->config->getPort()));
        $server->start($reactor, $errorHandler);
        return $server;
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
}
