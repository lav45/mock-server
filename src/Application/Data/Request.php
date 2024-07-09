<?php declare(strict_types=1);

namespace lav45\MockServer\Application\Data;

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
