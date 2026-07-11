<?php declare(strict_types=1);

namespace Lav45\MockServer\Extension\Collection\Repository;

final readonly class OffsetPage
{
    public function __construct(
        public array $items,
        public int   $totalItems,
        public int   $currentPage,
        public int   $totalPages,
        public int   $pageSize,
    ) {}
}
