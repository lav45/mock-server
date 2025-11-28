<?php declare(strict_types=1);

namespace Lav45\MockServer;

use FastRoute\BadRouteException;
use FastRoute\Dispatcher;
use Lav45\MockServer\Infrastructure\Service\FileSystem;
use Lav45\Watcher\Event;
use Lav45\Watcher\WatcherInterface as FileWatcher;
use Psr\Log\LoggerInterface;

use function Amp\File\read;

final class Watcher implements WatcherInterface
{
    private Dispatcher $dispatcher;
    /** @var array<string,array<array>> */
    private array $mocksData = [];

    private FileWatcher $watcher;

    private bool $flashMocks = false;

    public function __construct(
        private readonly DispatcherFactoryInterface $dispatcherFactory,
        private readonly string                     $watchDir,
        private readonly LoggerInterface            $logger,
    ) {}

    public function init(): void
    {
        $files = $this->getFileList($this->watchDir);
        $this->mocksData = $this->parseFiles($files);
        $this->initDispatcher();
    }

    public function run(FileWatcher $watcher, \Closure $delay): void
    {
        $this->initWatcher($watcher, $this->watchDir);

        while (true) {
            $this->readWatcher();
            if ($this->flashMocks) {
                $this->initDispatcher();
                $this->flashMocks = false;
            }
            $delay();
        }
    }

    private function readWatcher(): void
    {
        $this->watcher->read();
    }

    private function initWatcher(FileWatcher $watcher, string $watchDir): void
    {
        $this->watcher = $watcher
            ->on(IN_CREATE | IN_MOVED_TO, $this->handleCreateOrMoveToEvent(...))
            ->on(IN_DELETE | IN_MOVED_FROM, $this->handleDeleteOrMoveFromEvent(...))
            ->on(IN_CLOSE_WRITE, $this->handleCloseWriteEvent(...))
            ->withFilter(fn(Event $event): bool => $this->getFileFilter($event->path))
            ->watchDirs(FileSystem::getDirList($watchDir));
    }

    private function handleCreateOrMoveToEvent(Event $event): void
    {
        $this->onSetFile('Create ' . $event->path, $event->path);
    }

    private function handleDeleteOrMoveFromEvent(Event $event): void
    {
        $this->onDeleteFile('Delete ' . $event->path, $event->path);
    }

    private function handleCloseWriteEvent(Event $event): void
    {
        $this->onSetFile('Update ' . $event->path, $event->path);
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
        return $this->dispatcherFactory->create($data);
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
                $this->logger->debug("Parse: {$file}");
            } catch (\Throwable $exception) {
                $this->logger->error($exception);
                continue;
            }
        }
        return $result;
    }

    private function parseFile(string $file): array
    {
        return \json_decode(read($file), true, flags: JSON_THROW_ON_ERROR);
    }

    private function getFileFilter(string $path): bool
    {
        $folders = \explode(DIRECTORY_SEPARATOR, $path);
        $folders = \array_splice($folders, 1, -1);

        foreach ($folders as $folder) {
            if (\str_starts_with($folder, '__')) {
                return false;
            }
        }

        $filename = \basename($path);
        return \str_ends_with($filename, '.json')
            && \str_starts_with($filename, '.') === false;
    }

    private function getFileList(string $dir): array
    {
        return FileSystem::getFileList($dir, fn(string $path): bool => $this->getFileFilter($path));
    }

    private function onSetFile(string $log, string $file): void
    {
        $this->logger->debug($log);
        try {
            $this->mocksData[$file] = $this->parseFile($file);
            $this->flashMocks = true;
        } catch (\Throwable $exception) {
            $this->logger->error($exception);
        }
    }

    private function onDeleteFile(string $log, string $file): void
    {
        $this->logger->debug($log);
        unset($this->mocksData[$file]);
        $this->flashMocks = true;
    }
}
