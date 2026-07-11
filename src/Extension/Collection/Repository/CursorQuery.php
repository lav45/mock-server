<?php declare(strict_types=1);

namespace Lav45\MockServer\Extension\Collection\Repository;

final readonly class CursorQuery
{
    public function __construct(
        public string      $primaryKey,
        public string|null $after,
        public string|null $before,
        public int         $limit,
    ) {}
}
