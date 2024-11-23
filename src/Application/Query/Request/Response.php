<?php declare(strict_types=1);

namespace Lav45\MockServer\Application\Query\Request;

final readonly class Response
{
    public function __construct(
        public int    $status = 200,
        public array  $headers = [],
        public string $body = '',
    ) {}
}
