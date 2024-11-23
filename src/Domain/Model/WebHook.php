<?php declare(strict_types=1);

namespace Lav45\MockServer\Domain\Model;

use Lav45\MockServer\Domain\Model\Response\Body;
use Lav45\MockServer\Domain\Model\Response\Delay;
use Lav45\MockServer\Domain\Model\Response\HttpHeaders;
use Lav45\MockServer\Domain\Model\Response\HttpMethod;
use Lav45\MockServer\Domain\Model\Response\Url;

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
