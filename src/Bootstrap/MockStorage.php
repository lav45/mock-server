<?php declare(strict_types=1);

namespace Lav45\MockServer\Bootstrap;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final readonly class MockStorage
{
    public function __construct(
        private string          $mockStoragePath,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    /**
     * @return array<string,array<array>>
     */
    public function getFiles(): iterable
    {
        return $this->parseFiles(
            $this->getFileList($this->mockStoragePath),
        );
    }

    /**
     * @return array<array>
     */
    public function getData(): iterable
    {
        foreach ($this->getFiles() as $file => $data) {
            foreach ($data as $index => $mock) {
                yield "{$file}[{$index}]" => $mock;
            }
        }
    }

    private function getFileList(string $directory): iterable
    {
        return FileSystem::getFileList($directory, fn(string $path): bool => $this->isFilteredFile($path));
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
     * @return array<array>
     */
    private function parseFiles(iterable $files): iterable
    {
        foreach ($files as $file) {
            try {
                yield $file => $this->parseFile($file);
                $this->logger->debug("Parse: {$file}");
            } catch (\Throwable $exception) {
                $this->logger->error($exception);
                continue;
            }
        }
    }

    private function parseFile(string $file): array
    {
        return \json_decode(\file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);
    }
}
