<?php declare(strict_types=1);

namespace Lav45\MockServer\Bootstrap\Watcher;

interface FileStorageInterface
{
    public function getFiles(): array;

    public function isFilteredFile(string $path): bool;

    public function setFile(string $file): void;

    public function deleteFile(string $file): void;
}
