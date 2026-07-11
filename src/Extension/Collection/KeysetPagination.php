<?php declare(strict_types=1);

namespace Lav45\MockServer\Extension\Collection;

final readonly class KeysetPagination implements CursorPagination
{
    public function __construct(
        public string $afterParam,
        public string $beforeParam,
        public string $limitParam,
        public int    $defaultPageSize,
        public string $primaryKey,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            afterParam: $data['afterParam'] ?? 'after',
            beforeParam: $data['beforeParam'] ?? 'before',
            limitParam: $data['limitParam'] ?? 'limit',
            defaultPageSize: (int)($data['defaultPageSize'] ?? 20),
            primaryKey: $data['primaryKey'] ?? 'id',
        );
    }
}
