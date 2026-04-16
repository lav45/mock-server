<?php declare(strict_types=1);

namespace Lav45\MockServer\Domain\Mock\Response;

use Lav45\MockServer\Domain\Mock\Response;

final readonly class ProxyResponse implements Response
{
    public function __construct(
        private Delay      $delay,
        public Url         $url,
        public HttpMethod  $method,
        public HttpHeaders $headers,
        public Body        $body,
    ) {}

    public function delay(): float
    {
        return $this->delay->value;
    }
}
