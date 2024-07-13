<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Service;

final readonly class FileSystem
{
    public static function getFileList(string $directory, \Closure|null $filter = null): array
    {
        $list = [];
        $items = \glob($directory . '/*');
        foreach ($items as $path) {
            if (\is_dir($path)) {
                $list += self::getFileList($path);
            } elseif ($filter === null || $filter($path) === true) {
                $list[$path] = $path;
            }
        }
        return $list;
    }

    public static function getDirList(string $directory): array
    {
        $list = [$directory => $directory];
        $dirList = \glob($directory . '/*', GLOB_ONLYDIR);
        foreach ($dirList as $path) {
            $list += self::getDirList($path);
        }
        return $list;
    }
}
