<?php declare(strict_types=1);

namespace Lav45\MockServer;

use FastRoute\BadRouteException;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Lav45\MockServer\Infrastructure\Component\ArrayHelper;
use Lav45\MockServer\Infrastructure\Service\FileSystem;
use Lav45\Watcher\Event;
use Lav45\Watcher\Listener;
use Lav45\Watcher\Watcher as FileWatcher;
use Psr\Log\LoggerInterface;

use function Amp\delay;
use function FastRoute\simpleDispatcher;

final class Watcher implements WatcherInterface
{
    private Dispatcher $dispatcher;
    /** @var array<string,array<array>> */
    private array $mocksData = [];

    public function __construct(
        private readonly RequestFactoryInterface $requestFactory,
        private readonly string                  $watchDir,
        private readonly LoggerInterface         $logger,
    ) {}

    public function init(): void
    {
        $files = $this->getFileList($this->watchDir);
        $this->mocksData = $this->parseFiles($files);
        $this->initDispatcher();
    }

    public function run(float $timeout = 0.2): void
    {
        $flashMocks = false;

        $watcher = (new FileWatcher(new Listener()))
            ->on(IN_CREATE | IN_MOVED_TO, function (Event $event) use (&$flashMocks) {
                $this->onSetFile($flashMocks, 'Create ' . $event->path, $event->path);
            })
            ->on(IN_DELETE | IN_MOVED_FROM, function (Event $event) use (&$flashMocks) {
                $this->onDeleteFile($flashMocks, 'Delete ' . $event->path, $event->path);
            })
            ->on(IN_CLOSE_WRITE, function (Event $event) use (&$flashMocks) {
                $this->onSetFile($flashMocks, 'Update ' . $event->path, $event->path);
            })
            ->withFilter(fn(Event $event): bool => $this->getFileFilter($event->path))
            ->watchDirs(FileSystem::getDirList($this->watchDir));

        while (true) {
            $watcher->read();
            if ($flashMocks) {
                $this->initDispatcher();
                $flashMocks = false;
            }
            delay($timeout);
        }
    }

    public function getDispatcher(): Dispatcher
    {
        return $this->dispatcher;
    }

    private function initDispatcher(): void
    {
        $this->logger->debug('Init dispatcher');
        try {
            $this->dispatcher = $this->createDispatcher($this->mocksData);
        } catch (BadRouteException $exception) {
            $this->logger->error($exception);
        }
    }

    /**
     * @param array<string,array<array>> $data
     */
    private function createDispatcher(iterable $data): Dispatcher
    {
        return simpleDispatcher(function (RouteCollector $router) use ($data): void {
            foreach ($data as $mocks) {
                foreach ($mocks as $mock) {
                    $router->addRoute(
                        ArrayHelper::getValue($mock, 'request.method', ['GET']),
                        ArrayHelper::getValue($mock, 'request.url', '/'),
                        $this->requestFactory->create($mock),
                    );
                }
            }
        });
    }

    /**
     * @param string[] $files
     * @return array<string,array<array>>
     */
    private function parseFiles(iterable $files): array
    {
        $result = [];
        foreach ($files as $file) {
            try {
                $result[$file] = $this->parseFile($file);
            } catch (\Throwable $exception) {
                $this->logger->error($exception);
                continue;
            }
        }
        return $result;
    }

    private function parseFile(string $file): array
    {
        $content = \file_get_contents($file);
        return \json_decode($content, true, flags: JSON_THROW_ON_ERROR);
    }

    private function getFileFilter(string $filename): bool
    {
        return \str_ends_with($filename, '.json')
            && \str_starts_with($filename, '.') === false;
    }

    private function getFileList(string $dir): array
    {
        return FileSystem::getFileList($dir, fn(string $filename) => $this->getFileFilter($filename));
    }

    private function onSetFile(bool &$flashMocks, string $log, string $file): void
    {
        $this->logger->debug($log);
        try {
            $this->mocksData[$file] = $this->parseFile($file);
            $flashMocks = true;
        } catch (\Throwable $exception) {
            $this->logger->error($exception);
        }
    }

    private function onDeleteFile(bool &$flashMocks, string $log, string $file): void
    {
        $this->logger->debug($log);
        unset($this->mocksData[$file]);
        $flashMocks = true;
    }
}
