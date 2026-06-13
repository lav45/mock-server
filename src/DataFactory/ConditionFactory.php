<?php declare(strict_types=1);

namespace Lav45\MockServer\DataFactory;

use Lav45\MockServer\Domain\Conditions;

final readonly class ConditionFactory
{
    public const string TYPE = 'conditions';

    public function create(array $data): Conditions
    {
        return Conditions::fromArray($data);
    }
}
