<?php declare(strict_types=1);

namespace Lav45\MockServer\DataFactory\Condition;

use Lav45\MockServer\Domain\Conditions;
use Lav45\MockServer\Domain\ValueObject\Value;
use Lav45\MockServer\Parser\InlineParser;

final readonly class ConditionHandler
{
    public function __construct(
        private SpecificationFactory $specFactory,
    ) {}

    public function request(Conditions $conditions, InlineParser $parser): array
    {
        foreach ($conditions->items as $condition) {
            if ($this->matches($condition->match, $parser)) {
                $result = ['response' => $condition->response];
                if ($condition->webhooks !== Value::Undefined) {
                    $result['webhooks'] = $condition->webhooks;
                }
                return $result;
            }
        }
        return [];
    }

    public function matches(array $expression, InlineParser $parser): bool
    {
        if (empty($expression)) {
            return true;
        }
        return $this->specFactory->create($expression, $parser)->isSatisfiedBy(null);
    }
}
