<?php declare(strict_types=1);

namespace Lav45\MockServer\Domain\Model;

final readonly class Mock
{
    public function __construct(
        public Response $response,
        public iterable $webHooks,
    ) {}
}
