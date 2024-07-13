<?php declare(strict_types=1);

namespace Lav45\MockServer\Domain\Entity;

use Lav45\MockServer\Domain\ValueObject\Webhook;

final readonly class Mock
{
    public function __construct(
        public Response $response,
        /** @var Webhook[] */
        public iterable $webhooks = [],
    ) {}
}
