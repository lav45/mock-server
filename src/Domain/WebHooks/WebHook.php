<?php declare(strict_types=1);

namespace Lav45\MockServer\Domain\WebHooks;

use Lav45\MockServer\Domain\ValueObject\Body;
use Lav45\MockServer\Domain\ValueObject\Delay;
use Lav45\MockServer\Domain\ValueObject\HttpHeaders;
use Lav45\MockServer\Domain\ValueObject\HttpMethod;
use Lav45\MockServer\Domain\ValueObject\Url;

final readonly class WebHook
{
    public function __construct(
        public Delay       $delay,
        public Url         $url,
        public HttpMethod  $method,
        public HttpHeaders $headers,
        public Body        $body,
    ) {}
}
