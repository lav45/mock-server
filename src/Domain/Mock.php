<?php declare(strict_types=1);

namespace Lav45\MockServer\Domain;

use Lav45\MockServer\Domain\Mock\Response;

final readonly class Mock
{
    public function __construct(
        public Response $response,
        public iterable $webHooks,
    ) {}
}
