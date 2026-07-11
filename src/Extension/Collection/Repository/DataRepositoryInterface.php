<?php declare(strict_types=1);

namespace Lav45\MockServer\Extension\Collection\Repository;

interface DataRepositoryInterface
{
    public function offsetPage(OffsetQuery $query): OffsetPage;

    public function cursorPage(CursorQuery $query): CursorPage;
}
