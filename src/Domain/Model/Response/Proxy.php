<?php declare(strict_types=1);

namespace Lav45\MockServer\Domain\Model\Response;

use Lav45\MockServer\Domain\Model\Response;

final readonly class Proxy implements Response
{
    public function __construct(
        public Start       $start,
        public Delay       $delay,
        public Url         $url,
        public HttpMethod  $method,
        public HttpHeaders $headers,
        public Body        $body,
    ) {}
}
