<?php declare(strict_types=1);

namespace Lav45\MockServer\Domain\Response;

use Lav45\MockServer\Domain\ValueObject\Body;
use Lav45\MockServer\Domain\ValueObject\HttpHeaders;
use Lav45\MockServer\Domain\ValueObject\HttpMethod;
use Lav45\MockServer\Domain\ValueObject\Url;

final readonly class ProxyResponse
{
    public function __construct(
        public Url         $url,
        public HttpMethod  $method,
        public HttpHeaders $headers,
        public Body        $body,
    ) {}
}
