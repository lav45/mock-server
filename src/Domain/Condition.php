<?php declare(strict_types=1);

namespace Lav45\MockServer\Domain;

use Lav45\MockServer\Domain\ValueObject\Value;

final readonly class Condition
{
    public function __construct(
        public array       $match,
        public array       $response,
        public array|Value $webhooks,
    ) {}

    public static function fromArray(array $item): self
    {
        return new self(
            match: $item['match'],
            response: $item['response'],
            webhooks: $item['webhooks'] ?? Value::Undefined,
        );
    }
}
