<?php declare(strict_types=1);

namespace Lav45\MockServer\Domain\Entity\Response;

use Lav45\MockServer\Domain\Entity\Response;
use Lav45\MockServer\Domain\Factory\Response\Body;
use Lav45\MockServer\Domain\Factory\Response\HttpHeaders;
use Lav45\MockServer\Domain\ValueObject\Response\Delay;
use Lav45\MockServer\Domain\ValueObject\Response\HttpStatus;
use Lav45\MockServer\Domain\ValueObject\Response\Pagination;

final readonly class Collection implements Response
{
    public function __construct(
        public Delay       $delay,
        public HttpStatus  $status,
        public HttpHeaders $headers,
        public Body        $body,
        public Pagination  $pagination,
        public array       $items,
    ) {}
}
