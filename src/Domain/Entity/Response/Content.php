<?php declare(strict_types=1);

namespace lav45\MockServer\Domain\Entity\Response;

use lav45\MockServer\Domain\Entity\Response;
use lav45\MockServer\Domain\ValueObject\Response\Body;
use lav45\MockServer\Domain\ValueObject\Response\Delay;
use lav45\MockServer\Domain\ValueObject\Response\HttpHeaders;
use lav45\MockServer\Domain\ValueObject\Response\HttpStatus;

final readonly class Content implements Response
{
    public function __construct(
        public Delay       $delay,
        public HttpStatus  $status,
        public HttpHeaders $headers,
        public Body        $body,
    )
    {
    }
}