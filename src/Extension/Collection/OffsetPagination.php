<?php declare(strict_types=1);

namespace Lav45\MockServer\Extension\Collection;

final readonly class OffsetPagination
{
    public function __construct(
        public string $pageParam,
        public string $pageSizeParam,
        public int    $defaultPageSize,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            pageParam: $data['pageParam'] ?? 'page',
            pageSizeParam: $data['pageSizeParam'] ?? 'per-page',
            defaultPageSize: (int)($data['defaultPageSize'] ?? 20),
        );
    }
}
