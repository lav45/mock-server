<?php declare(strict_types=1);

namespace Lav45\MockServer;

use Lav45\MockServer\Infrastructure\Service\FileSystem;
use Psr\Log\LoggerInterface;

use function Amp\File\read;

final class FileStorage implements Watcher\FileStorage
{
    /** @var array<string,array<array>> */
    private array $files;

    public function __construct(
        string                           $watchDir,
        private readonly LoggerInterface $logger,
    ) {
        $files = $this->getFileList($watchDir);
        $this->files = $this->parseFiles($files);
    }

    public function getFiles(): array
    {
        return $this->files;
    }

    private function getFileList(string $dir): array
    {
        return FileSystem::getFileList($dir, fn(string $path): bool => $this->isFilteredFile($path));
    }

    public function isFilteredFile(string $path): bool
    {
        $folders = \explode(DIRECTORY_SEPARATOR, $path);
        $folders = \array_splice($folders, 1, -1);

        $filter = static fn($folder) => \str_starts_with($folder, '__');
        if (\array_any($folders, $filter)) {
            return false;
        }

        $filename = \basename($path);
        return \str_ends_with($filename, '.json')
            && \str_starts_with($filename, '.') === false;
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

    public function setFile(string $file): void
    {
        $this->files[$file] = $this->parseFile($file);
    }

    public function deleteFile(string $file): void
    {
        unset($this->files[$file]);
    }
}
