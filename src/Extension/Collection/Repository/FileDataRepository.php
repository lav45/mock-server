<?php declare(strict_types=1);

namespace Lav45\MockServer\Extension\Collection\Repository;

use function Amp\File\read;

final readonly class FileDataRepository implements DataRepositoryInterface
{
    private ArrayDataRepository $repository;

    public function __construct(string $path)
    {
        $content = read($path);
        $items = \json_decode($content, associative: true, flags: JSON_THROW_ON_ERROR);
        $this->repository = new ArrayDataRepository($items);
    }

    public function offsetPage(OffsetQuery $query): OffsetPage
    {
        return $this->repository->offsetPage($query);
    }

    public function cursorPage(CursorQuery $query): CursorPage
    {
        return $this->repository->cursorPage($query);
    }
}
