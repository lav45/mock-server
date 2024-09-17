<?php declare(strict_types=1);

namespace Lav45\MockServer\Application\Data\Mock\v1;

use Lav45\MockServer\Application\Data\Mock\v1\Response\Content;
use Lav45\MockServer\Application\Data\Mock\v1\Response\Data;
use Lav45\MockServer\Application\Data\Mock\v1\Response\Proxy;

/**
 * @codeCoverageIgnore
 */
final readonly class Response
{
    public function __construct(
        public Content|null $content = null,
        public Proxy|null   $proxy = null,
        public Data|null    $data = null,
        public float|string $delay = 0.0,
    ) {}
}
