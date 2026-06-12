<?php declare(strict_types=1);

namespace Lav45\MockServer\Bootstrap;

final readonly class FileSystem
{
    public static function getFileList(string $directory, \Closure|null $filter = null): iterable
    {
        $items = \glob($directory . '/*');
        foreach ($items as $path) {
            if (\is_dir($path)) {
                yield from self::getFileList($path, $filter);
            } elseif (\is_file($path) && ($filter === null || $filter($path) === true)) {
                yield $path => $path;
            }
        }
    }
}
