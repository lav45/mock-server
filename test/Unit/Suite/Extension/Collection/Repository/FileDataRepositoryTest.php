<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Extension\Collection\Repository;

use Lav45\MockServer\Extension\Collection\Repository\CursorQuery;
use Lav45\MockServer\Extension\Collection\Repository\FileDataRepository;
use Lav45\MockServer\Extension\Collection\Repository\OffsetQuery;
use PHPUnit\Framework\TestCase;

final class FileDataRepositoryTest extends TestCase
{
    private string $file;

    protected function setUp(): void
    {
        $this->file = \tempnam(\sys_get_temp_dir(), 'data');
        \file_put_contents($this->file, \json_encode([
            ['id' => 1],
            ['id' => 2],
            ['id' => 3],
        ], JSON_THROW_ON_ERROR));
    }

    protected function tearDown(): void
    {
        \unlink($this->file);
    }

    public function testOffsetPageReadsFromFile(): void
    {
        $page = new FileDataRepository($this->file)->offsetPage(new OffsetQuery(page: 1, limit: 2));

        $this->assertSame([['id' => 1], ['id' => 2]], $page->items);
        $this->assertSame(3, $page->totalItems);
    }

    public function testCursorPageReadsFromFile(): void
    {
        $page = new FileDataRepository($this->file)->cursorPage(new CursorQuery(
            primaryKey: 'id',
            after: null,
            before: null,
            limit: 2,
        ));

        $this->assertSame([1, 2], \array_column($page->items, 'id'));
        $this->assertTrue($page->hasNext);
    }
}
