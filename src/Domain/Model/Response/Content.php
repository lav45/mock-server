<?php declare(strict_types=1);

namespace Lav45\MockServer\Domain\Model\Response;

use Lav45\MockServer\Domain\Model\Response;

final readonly class Content implements Response
{
    public function __construct(
        public Start       $start,
        public Delay       $delay,
        public HttpStatus  $status,
        public HttpHeaders $headers,
        public Body        $body,
    ) {}
}
