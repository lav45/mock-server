<?php declare(strict_types=1);

namespace Lav45\MockServer\Domain\Condition\Specification;

use Lav45\MockServer\Domain\Condition\Specification;
use Lav45\MockServer\Domain\ValueObject\Value;

final class ExistsSpecification implements Specification
{
    public function isSatisfiedBy(mixed $actual): bool
    {
        return $actual !== Value::Undefined;
    }
}
