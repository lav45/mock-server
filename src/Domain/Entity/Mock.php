<?php declare(strict_types=1);

namespace lav45\MockServer\Domain\Entity;

use lav45\MockServer\Domain\ValueObject\Webhook;

final readonly class Mock
{
    public function __construct(
        public Response $response,
        /** @var Webhook[] */
        public iterable $webhooks = [],
    ) {}
}
