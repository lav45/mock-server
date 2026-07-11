<?php declare(strict_types=1);

namespace Lav45\MockServer\Extension\Collection;

final readonly class IteratorPagination implements CursorPagination
{
    public function __construct(
        public string $iteratorParam,
        public string $limitParam,
        public int    $defaultPageSize,
        public string $primaryKey,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            iteratorParam: $data['iteratorParam'] ?? 'iterator',
            limitParam: $data['limitParam'] ?? 'limit',
            defaultPageSize: (int)($data['defaultPageSize'] ?? 20),
            primaryKey: $data['primaryKey'] ?? 'id',
        );
    }
}
