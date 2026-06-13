<?php declare(strict_types=1);

namespace Lav45\MockServer\Bootstrap;

use Lav45\MockServer\Middleware\Pipeline;

final readonly class Migrator
{
    public static function create(string $path): \Closure
    {
        $files = \glob($path . '/v*_*.php') ?: [];
        \sort($files, SORT_NATURAL);

        $migrates = [];
        foreach ($files as $file) {
            $class = \basename($file, '.php');
            $migrates[] = new $class();
        }

        $migrates[] = static fn(array $data): array => $data;

        return Pipeline::create(...$migrates);
    }
}
