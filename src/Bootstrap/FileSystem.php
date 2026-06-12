<?php declare(strict_types=1);

namespace Lav45\MockServer\Bootstrap;

final readonly class FileSystem
{
    public static function getFileList(string $directory, \Closure|null $filter = null): array
    {
        $list = [];
        $items = \glob($directory . '/*');
        foreach ($items as $path) {
            if (\is_dir($path)) {
                $list += self::getFileList($path, $filter);
            } elseif (\is_file($path) && ($filter === null || $filter($path) === true)) {
                $list[$path] = $path;
            }
        }
        return $list;
    }
}
