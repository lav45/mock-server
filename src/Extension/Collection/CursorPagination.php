<?php declare(strict_types=1);

namespace Lav45\MockServer\Extension\Collection;

interface CursorPagination
{
    public string $limitParam { get; }
    public int $defaultPageSize { get; }
    public string $primaryKey { get; }
}
