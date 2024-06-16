<?php declare(strict_types=1);

namespace lav45\MockServer\Domain\ValueObject\Response;

final readonly class Pagination
{
    public function __construct(
        public string $pageParam,
        public string $pageSizeParam,
        public int    $defaultPageSize,
    ) {
        \assert($this->isValidParamName($pageParam), 'Invalid pageParam');
        \assert($this->isValidParamName($pageSizeParam), 'Invalid pageSizeParam');
        \assert($this->isValidDefaultPageSize($defaultPageSize), 'Invalid defaultPageSize');
    }

    private function isValidParamName(string $param): bool
    {
        return (bool)\preg_match('~^[\w\-]+$~', $param);
    }

    private function isValidDefaultPageSize(int $value): bool
    {
        return $value > 0;
    }
}
