<?php declare(strict_types=1);

namespace lav45\MockServer\Application\DTO\Mock\v1;

use lav45\MockServer\Application\DTO\Mock\v1\Response\Content;
use lav45\MockServer\Application\DTO\Mock\v1\Response\Data;
use lav45\MockServer\Application\DTO\Mock\v1\Response\Proxy;

/**
 * @codeCoverageIgnore
 */
final readonly class Response
{
    public function __construct(
        public Content|null $content = null,
        public Proxy|null   $proxy = null,
        public Data|null    $data = null,
        /** @var float|string */
        public mixed        $delay = 0.0,
    )
    {
    }
}