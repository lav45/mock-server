<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Extension\Collection\Repository;

use Lav45\MockServer\Extension\Collection\Repository\ArrayDataRepository;
use Lav45\MockServer\Extension\Collection\Repository\CursorQuery;
use Lav45\MockServer\Extension\Collection\Repository\OffsetQuery;
use PHPUnit\Framework\TestCase;

final class ArrayDataRepositoryTest extends TestCase
{
    private array $items = [
        ['id' => 'a'],
        ['id' => 'b'],
        ['id' => 'c'],
        ['id' => 'd'],
        ['id' => 'e'],
        ['id' => 'f'],
    ];

    private function cursor(string|null $after, string|null $before, int $limit): CursorQuery
    {
        return new CursorQuery(
            primaryKey: 'id',
            after: $after,
            before: $before,
            limit: $limit,
        );
    }

    public function testOffsetPageReturnsSourceOrder(): void
    {
        $repository = new ArrayDataRepository([['id' => 1], ['id' => 2], ['id' => 3]]);

        $page = $repository->offsetPage(new OffsetQuery(page: 1, limit: 2));

        $this->assertSame([['id' => 1], ['id' => 2]], $page->items);
        $this->assertSame(3, $page->totalItems);
        $this->assertSame(1, $page->currentPage);
        $this->assertSame(2, $page->totalPages);
        $this->assertSame(2, $page->pageSize);
    }

    public function testOffsetPageOutOfRange(): void
    {
        $repository = new ArrayDataRepository([['id' => 1], ['id' => 2]]);

        $page = $repository->offsetPage(new OffsetQuery(page: 100, limit: 5));

        $this->assertSame([], $page->items);
        $this->assertSame(0, $page->pageSize);
        $this->assertSame(2, $page->totalItems);
    }

    public function testCursorFirstPage(): void
    {
        $page = new ArrayDataRepository($this->items)->cursorPage($this->cursor(null, null, 2));

        $this->assertSame(['a', 'b'], \array_column($page->items, 'id'));
        $this->assertTrue($page->hasNext);
        $this->assertFalse($page->hasPrev);
    }

    public function testCursorAfter(): void
    {
        $page = new ArrayDataRepository($this->items)->cursorPage($this->cursor('b', null, 2));

        $this->assertSame(['c', 'd'], \array_column($page->items, 'id'));
        $this->assertTrue($page->hasNext);
        $this->assertTrue($page->hasPrev);
    }

    public function testCursorBefore(): void
    {
        $page = new ArrayDataRepository($this->items)->cursorPage($this->cursor(null, 'e', 2));

        $this->assertSame(['c', 'd'], \array_column($page->items, 'id'));
        $this->assertTrue($page->hasNext);
        $this->assertTrue($page->hasPrev);
    }

    public function testCursorLastPartialPage(): void
    {
        $page = new ArrayDataRepository($this->items)->cursorPage($this->cursor('e', null, 2));

        $this->assertSame(['f'], \array_column($page->items, 'id'));
        $this->assertFalse($page->hasNext);
        $this->assertTrue($page->hasPrev);
    }

    public function testCursorUnknownFallsBackToFirstPage(): void
    {
        $page = new ArrayDataRepository($this->items)->cursorPage($this->cursor('zzz', null, 2));

        $this->assertSame(['a', 'b'], \array_column($page->items, 'id'));
        $this->assertFalse($page->hasPrev);
    }
}
