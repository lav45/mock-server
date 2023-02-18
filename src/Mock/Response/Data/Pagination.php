<?php

namespace lav45\MockServer\Mock\Response\Data;

use lav45\MockServer\components\DTObject;

/**
 * Class Pagination
 * @package lav45\MockServer\Mock\Response\Data
 */
class Pagination extends DTObject
{
    /** @var string */
    public string $pageParam = 'page';
    /** @var string */
    public string $pageSizeParam = 'per-page';
    /** @var int */
    public int $defaultPageSize = 20;
}