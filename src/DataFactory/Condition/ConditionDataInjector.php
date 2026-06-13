<?php declare(strict_types=1);

namespace Lav45\MockServer\DataFactory\Condition;

use Lav45\MockServer\Domain\ValueObject\Value;

final readonly class ConditionDataInjector
{
    public function __construct(
        private array|Value $response = Value::Undefined,
        private array|Value $webhooks = Value::Undefined,
    ) {}

    public function replace(array $data): array
    {
        if ($this->response !== Value::Undefined) {
            $data['response'] = $this->response;
        }
        if ($this->webhooks !== Value::Undefined) {
            $data['webhooks'] = $this->webhooks;
        }
        return $data;
    }
}
