<?php declare(strict_types=1);

namespace lav45\MockServer\Application\Data;

final readonly class Response
{
    public function __construct(
        public int    $status = 200,
        public array  $headers = [],
        public string $body = '',
    ) {}
}
