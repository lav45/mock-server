<?php declare(strict_types=1);

namespace Lav45\MockServer\Domain\Response;

use Lav45\MockServer\Domain\ValueObject\Body;
use Lav45\MockServer\Domain\ValueObject\HttpHeaders;
use Lav45\MockServer\Domain\ValueObject\HttpStatus;

final readonly class ContentResponse
{
    public function __construct(
        public HttpStatus  $status,
        public HttpHeaders $headers,
        public Body        $body,
    ) {}
}
