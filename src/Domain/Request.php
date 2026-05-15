<?php declare(strict_types=1);

namespace Lav45\MockServer\Domain;

use Lav45\MockServer\Domain\Request\HttpMethods;
use Lav45\MockServer\Domain\Request\Path;

final readonly class Request
{
    public function __construct(
        public HttpMethods $methods,
        public Path        $path,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            methods: HttpMethods::fromArray((array)($data['method'] ?? 'GET')),
            path: new Path($data['path'] ?? '/'),
        );
    }
}
