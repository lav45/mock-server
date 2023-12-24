<?php declare(strict_types=1);

namespace lav45\MockServer\Mock\Response\Data;

use lav45\MockServer\Component\DTObject;

class Pagination extends DTObject
{
    public string $pageParam = 'page';
    public string $pageSizeParam = 'per-page';
    public int $defaultPageSize = 20;
}