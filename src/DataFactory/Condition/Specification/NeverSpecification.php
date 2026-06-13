<?php declare(strict_types=1);

namespace Lav45\MockServer\DataFactory\Condition\Specification;

use Lav45\MockServer\DataFactory\Condition\Specification;

final class NeverSpecification implements Specification
{
    public function isSatisfiedBy(mixed $actual): bool
    {
        return false;
    }
}
