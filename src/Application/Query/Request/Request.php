<?php declare(strict_types=1);

namespace Lav45\MockServer\Application\Query\Request;

final readonly class Request
{
    public function __construct(
        public float  $start,
        public string $method,
        public array  $get,
        public array  $post,
        public array  $headers,
        public array  $urlParams,
        public string $body,
    ) {}
}
