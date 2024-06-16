<?php declare(strict_types=1);

namespace lav45\MockServer\Application\Factory\Entity;

use lav45\MockServer\Domain\Entity\Mock as MockEntity;
use lav45\MockServer\Domain\Service\Parser;

final readonly class Mock
{
    public function __construct(
        private Response $response,
        private Webhooks $webhooks,
    ) {}

    public function create(Parser $parser): MockEntity
    {
        return new MockEntity(
            response: $this->response->create($parser),
            webhooks: $this->webhooks->create($parser),
        );
    }
}
