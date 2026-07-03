<?php declare(strict_types=1);

namespace Lav45\MockServer\Extension\Condition;

use Lav45\MockServer\Domain\Condition;
use Lav45\MockServer\Domain\Conditions;
use Lav45\MockServer\Domain\ValueObject\Value;

final readonly class ConditionFactory
{
    private const string TYPE = 'conditions';

    public function __construct(
        private SpecificationFactory $factory,
    ) {}

    public function has(array $data): bool
    {
        return isset($data[self::TYPE]);
    }

    public function create(array $data): Conditions
    {
        $items = [];
        foreach ($data[self::TYPE] as $item) {
            $items[] = $this->createItem($item);
        }
        return new Conditions(...$items);
    }

    private function createItem(array $data): Condition
    {
        return new Condition(
            match: $this->factory->create($data['match']),
            response: $data['response'],
            webhooks: $data['webhooks'] ?? Value::Undefined,
        );
    }
}
