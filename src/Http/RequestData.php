<?php declare(strict_types=1);

namespace Lav45\MockServer\Http;

final readonly class RequestData
{
    public function __construct(
        public string $method,
        public array  $get,
        public array  $post,
        public array  $headers,
        public array  $urlParams,
        public string $body,
    ) {}

    public function toArray(): array
    {
        return \get_object_vars($this);
    }
}
