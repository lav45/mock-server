<?php declare(strict_types=1);

namespace lav45\MockServer\Domain\ValueObject;

use lav45\MockServer\Domain\ValueObject\Response\Body;
use lav45\MockServer\Domain\ValueObject\Response\Delay;
use lav45\MockServer\Domain\ValueObject\Response\HttpHeaders;
use lav45\MockServer\Domain\ValueObject\Response\HttpMethod;
use lav45\MockServer\Domain\ValueObject\Response\Url;

final readonly class Webhook
{
    public function __construct(
        public Delay       $delay,
        public Url         $url,
        public HttpMethod  $method,
        public HttpHeaders $headers,
        public Body        $body,
    )
    {
    }
}