<?php declare(strict_types=1);

namespace Lav45\MockServer\DataFactory\Condition\Specification;

use Lav45\MockServer\DataFactory\Condition\Specification;

final readonly class NotSpecification implements Specification
{
    public function __construct(private Specification $spec) {}

    public function isSatisfiedBy(mixed $actual): bool
    {
        return $this->spec->isSatisfiedBy($actual) === false;
    }
}
