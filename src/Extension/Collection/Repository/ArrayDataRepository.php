<?php declare(strict_types=1);

namespace Lav45\MockServer\Extension\Collection\Repository;

use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Paginator\PageNotFoundException;
use Yiisoft\Data\Reader\Iterable\IterableDataReader;

final readonly class ArrayDataRepository implements DataRepositoryInterface
{
    public function __construct(
        private array $items,
    ) {}

    public function offsetPage(OffsetQuery $query): OffsetPage
    {
        $paginator = new OffsetPaginator(new IterableDataReader($this->items))
            ->withPageSize($query->limit)
            ->withCurrentPage($query->page);

        try {
            $items = \iterator_to_array($paginator->read()) |> \array_values(...);
        } catch (PageNotFoundException) {
            $items = [];
        }

        return new OffsetPage(
            items: $items,
            totalItems: $paginator->getTotalItems(),
            currentPage: $paginator->getCurrentPage(),
            totalPages: $paginator->getTotalPages(),
            pageSize: $items ? $paginator->getCurrentPageSize() : 0,
        );
    }

    public function cursorPage(CursorQuery $query): CursorPage
    {
        $items = \array_values($this->items);
        $total = \count($items);
        $primaryKey = $query->primaryKey;
        $limit = $query->limit;

        if ($query->before !== null && ($pos = $this->cursorIndex($items, $primaryKey, $query->before)) !== null) {
            $offset = \max(0, $pos - $limit);
            $length = $pos - $offset;
        } elseif ($query->after !== null && ($pos = $this->cursorIndex($items, $primaryKey, $query->after)) !== null) {
            $offset = $pos + 1;
            $length = $limit;
        } else {
            $offset = 0;
            $length = $limit;
        }

        $page = \array_slice($items, $offset, $length) |> \array_values(...);
        $count = \count($page);

        return new CursorPage(
            items: $page,
            hasNext: $count > 0 && ($offset + $count) < $total,
            hasPrev: $count > 0 && $offset > 0,
        );
    }

    private function cursorIndex(array $items, string $primaryKey, string $cursor): int|null
    {
        return \array_find_key($items, static fn($item) => (string)($item[$primaryKey] ?? '') === $cursor);
    }
}
