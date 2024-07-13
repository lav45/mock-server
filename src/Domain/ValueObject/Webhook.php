<?php declare(strict_types=1);

namespace Lav45\MockServer\Domain\ValueObject;

use Lav45\MockServer\Domain\ValueObject\Response\Body;
use Lav45\MockServer\Domain\ValueObject\Response\Delay;
use Lav45\MockServer\Domain\ValueObject\Response\HttpHeaders;
use Lav45\MockServer\Domain\ValueObject\Response\HttpMethod;
use Lav45\MockServer\Domain\ValueObject\Response\Url;

final readonly class Webhook
{
    public function __construct(
        public Delay       $delay,
        public Url         $url,
        public HttpMethod  $method,
        public HttpHeaders $headers,
        public Body        $body,
    ) {}
}
