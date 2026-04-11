<?php declare(strict_types=1);

namespace Lav45\MockServer\Http;

final readonly class ResponseData
{
    public function __construct(
        public int    $status = 200,
        public array  $headers = [],
        public string $body = '',
    ) {}
}
