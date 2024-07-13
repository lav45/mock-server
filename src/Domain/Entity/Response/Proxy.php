<?php declare(strict_types=1);

namespace Lav45\MockServer\Domain\Entity\Response;

use Lav45\MockServer\Domain\Entity\Response;
use Lav45\MockServer\Domain\Factory\Response\Url;
use Lav45\MockServer\Domain\ValueObject\Response\Body;
use Lav45\MockServer\Domain\ValueObject\Response\Delay;
use Lav45\MockServer\Domain\ValueObject\Response\HttpHeaders;

final readonly class Proxy implements Response
{
    public function __construct(
        public Delay       $delay,
        public Url         $url,
        public HttpHeaders $headers,
        public Body        $content,
    ) {}
}
