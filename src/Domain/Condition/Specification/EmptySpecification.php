<?php declare(strict_types=1);

namespace Lav45\MockServer\Domain\Condition\Specification;

use Lav45\MockServer\Domain\Condition\Specification;
use Lav45\MockServer\Domain\ValueObject\Value;

final class EmptySpecification implements Specification
{
    public function isSatisfiedBy(mixed $actual): bool
    {
        if ($actual === Value::Undefined) {
            return false;
        }
        return $actual === null || $actual === '';
    }
}
