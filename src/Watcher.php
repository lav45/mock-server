<?php declare(strict_types=1);

namespace Lav45\MockServer;

use FastRoute\BadRouteException;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Lav45\MockServer\Application\Data\Mock\v1\Mock;
use Lav45\MockServer\Infrastructure\Controller\RequestFactory;
use Lav45\MockServer\Infrastructure\Factory\Mock as MockFactory;
use Lav45\MockServer\Infrastructure\Service\FileSystem;
use Lav45\Watcher\Event;
use Lav45\Watcher\Listener;
use Lav45\Watcher\Watcher as FileWatcher;
use Psr\Log\LoggerInterface;

use function Amp\delay;
use function FastRoute\simpleDispatcher;

final class Watcher
{
    private Dispatcher $dispatcher;
    /** @var array<string,array<array>> */
    private array $fileMocks = [];

    private bool $flashMocks = false;

    public function __construct(
        private readonly RequestFactory       $requestFactory,
        private readonly string               $watchDir,
        private readonly LoggerInterface|null $logger = null,
    ) {}

    public function init(): void
    {
        $files = $this->getFileList($this->watchDir);
        $this->fileMocks = $this->parseFiles($files);
        $this->initDispatcher();
    }

    public function run(float $timeout = 0.2): void
    {
        $watcher = (new FileWatcher(new Listener()))
            ->on(IN_CREATE | IN_MOVED_TO, fn(Event $event) => $this->onSetFile('Create ' . $event->path, $event->path))
            ->on(IN_DELETE | IN_MOVED_FROM, fn(Event $event) => $this->onDeleteFile('Delete ' . $event->path, $event->path))
            ->on(IN_CLOSE_WRITE, fn(Event $event) => $this->onSetFile('Update ' . $event->path, $event->path))
            ->withFilter(fn(Event $event): bool => $this->getFileFilter($event->path))
            ->watchDirs(FileSystem::getDirList($this->watchDir));

        while (true) {
            $this->flashMocks = false;
            $watcher->read();
            $this->flashMocks && $this->initDispatcher();
            delay($timeout);
        }
    }

    public function getDispatcher(): Dispatcher
    {
        return $this->dispatcher;
    }

    private function initDispatcher(): void
    {
        $this->logger?->debug('Init dispatcher');
        try {
            $mocks = $this->createMocks($this->fileMocks);
            $routes = $this->createRouters($mocks);
            $this->dispatcher = $this->createDispatcher($routes);
        } catch (BadRouteException $exception) {
            $this->logger?->error($exception);
        }
    }

    private function createDispatcher(iterable $routes): Dispatcher
    {
        return simpleDispatcher(function (RouteCollector $router) use ($routes): void {
            foreach ($routes as [$method, $url, $handler]) {
                $router->addRoute($method, $url, $handler);
            }
        });
    }

    /**
     * @param Mock[] $mocks
     */
    private function createRouters(iterable $mocks): iterable
    {
        foreach ($mocks as $mock) {
            yield [
                $mock->request->method,
                $mock->request->url,
                $this->requestFactory->create($mock),
            ];
        }
    }

    /**
     * @param array<string,array<array>> $files
     * @return Mock[]
     */
    private function createMocks(array $files): iterable
    {
        foreach ($files as $items) {
            foreach ($items as $item) {
                try {
                    yield MockFactory::create($item);
                } catch (\Throwable $exception) {
                    $this->logger?->error($exception);
                    continue;
                }
            }
        }
    }

    /**
     * @param string[] $files
     * @return array<string,array<array>>
     */
    private function parseFiles(array $files): array
    {
        $result = [];
        foreach ($files as $file) {
            try {
                $result[$file] = $this->parseFile($file);
            } catch (\Throwable $exception) {
                $this->logger?->error($exception);
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

    private function onSetFile(string $log, string $file): void
    {
        $this->logger?->debug($log);
        try {
            $this->fileMocks[$file] = $this->parseFile($file);
            $this->flashMocks = true;
        } catch (\Throwable $exception) {
            $this->logger?->error($exception);
        }
    }

    private function onDeleteFile(string $log, string $file): void
    {
        $this->logger?->debug($log);
        unset($this->fileMocks[$file]);
        $this->flashMocks = true;
    }
}
