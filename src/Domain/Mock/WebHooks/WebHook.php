<?php declare(strict_types=1);

namespace Lav45\MockServer\Domain\Mock\WebHooks;

use Lav45\MockServer\Domain\Mock\Response\Body;
use Lav45\MockServer\Domain\Mock\Response\Delay;
use Lav45\MockServer\Domain\Mock\Response\HttpHeaders;
use Lav45\MockServer\Domain\Mock\Response\HttpMethod;
use Lav45\MockServer\Domain\Mock\Response\Url;

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
