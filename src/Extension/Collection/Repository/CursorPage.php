<?php declare(strict_types=1);

namespace Lav45\MockServer\Extension\Collection\Repository;

final readonly class CursorPage
{
    public function __construct(
        public array $items,
        public bool  $hasNext,
        public bool  $hasPrev,
    ) {}
}
