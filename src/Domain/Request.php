<?php declare(strict_types=1);

namespace Lav45\MockServer\Domain;

use Lav45\MockServer\Domain\Request\HttpMethods;
use Lav45\MockServer\Domain\Request\Url;

final readonly class Request
{
    public function __construct(
        public HttpMethods $methods,
        public Url         $url,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            methods: HttpMethods::fromArray((array)($data['method'] ?? 'GET')),
            url: new Url($data['url'] ?? '/'),
        );
    }
}
