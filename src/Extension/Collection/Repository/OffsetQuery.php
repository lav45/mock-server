<?php declare(strict_types=1);

namespace Lav45\MockServer\Extension\Collection\Repository;

final readonly class OffsetQuery
{
    public function __construct(
        public int $page,
        public int $limit,
    ) {}
}
