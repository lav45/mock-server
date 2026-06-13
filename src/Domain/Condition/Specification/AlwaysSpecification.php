<?php declare(strict_types=1);

namespace Lav45\MockServer\Domain\Condition\Specification;

use Lav45\MockServer\Domain\Condition\Specification;

final class AlwaysSpecification implements Specification
{
    public function isSatisfiedBy(mixed $actual): bool
    {
        return true;
    }
}
