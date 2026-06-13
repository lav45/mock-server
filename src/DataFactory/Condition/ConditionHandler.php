<?php declare(strict_types=1);

namespace Lav45\MockServer\DataFactory\Condition;

final readonly class ConditionHandler
{
    public function request(array $conditions): ConditionDataInjector
    {
        foreach ($conditions as $condition) {
            if ($condition->match->isSatisfiedBy(null)) {
                return new ConditionDataInjector($condition->response, $condition->webhooks);
            }
        }
        return new ConditionDataInjector();
    }
}
