<?php declare(strict_types=1);

namespace lav45\MockServer\Application\Data\Mock\v1\Response\Data;

/**
 * @codeCoverageIgnore
 */
final readonly class Pagination
{
    public function __construct(
        public string $pageParam = 'page',
        public string $pageSizeParam = 'per-page',
        public int    $defaultPageSize = 20,
    ) {}
}
