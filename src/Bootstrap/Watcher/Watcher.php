<?php declare(strict_types=1);

namespace Lav45\MockServer\Bootstrap\Watcher;

use Amp\DeferredCancellation;
use FastRoute\Dispatcher;
use Lav45\Watcher\Event;
use Lav45\Watcher\WatcherInterface as FileWatcher;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

use function Amp\delay;

final class Watcher implements \Lav45\MockServer\Bootstrap\Watcher
{
    private Dispatcher $dispatcher;

    public function __construct(
        private readonly DispatcherFactory    $dispatcherFactory,
        private readonly string               $watchDir,
        private readonly FileStorageInterface $fileStorage,
        private readonly LoggerInterface      $logger = new NullLogger(),
    ) {
        $this->initDispatcher();
    }

    public function getDispatcher(): Dispatcher
    {
        return $this->dispatcher;
    }

    public function run(FileWatcher $watcher, float $delay, DeferredCancellation|null $cancellation = null): void
    {
        $flashMocks = false;
        $watcher = $watcher
            ->on(IN_CREATE | IN_MOVED_TO, function (Event $event) use (&$flashMocks) {
                $flashMocks = $this->callFileStorage(function () use ($event): void {
                    $this->logger->debug('Create ' . $event->path);
                    $this->fileStorage->setFile($event->path);
                });
            })
            ->on(IN_DELETE | IN_MOVED_FROM, function (Event $event) use (&$flashMocks) {
                $flashMocks = $this->callFileStorage(function () use ($event): void {
                    $this->logger->debug('Delete ' . $event->path);
                    $this->fileStorage->deleteFile($event->path);
                });
            })
            ->on(IN_MODIFY, function (Event $event) use (&$flashMocks) {
                $flashMocks = $this->callFileStorage(function () use ($event): void {
                    $this->logger->debug('Update ' . $event->path);
                    $this->fileStorage->setFile($event->path);
                });
            })
            ->withFilter(fn(Event $event): bool => $this->fileStorage->isFilteredFile($event->path))
            ->watchDirs(FileSystem::getDirList($this->watchDir));

        do {
            $watcher->read();
            if ($flashMocks) {
                $this->initDispatcher();
                $flashMocks = false;
            }
            delay($delay);
            $isCancelled = $cancellation?->isCancelled() ?? false;
        } while ($isCancelled === false);
    }

    private function initDispatcher(): void
    {
        try {
            $this->dispatcher = $this->dispatcherFactory->create(
                $this->fileStorage->getFiles(),
            );
        } catch (\Throwable $exception) {
            $this->logger->error($exception);
        }
    }

    private function callFileStorage(\Closure $fn): bool
    {
        try {
            $fn();
            return true;
        } catch (\Throwable $exception) {
            $this->logger->error($exception);
        }
        return false;
    }
}
