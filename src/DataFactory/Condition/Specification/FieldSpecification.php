<?php declare(strict_types=1);

namespace Lav45\MockServer\DataFactory\Condition\Specification;

use Lav45\MockServer\DataFactory\Condition\Specification;

final readonly class FieldSpecification implements Specification
{
    public function __construct(
        private mixed         $actual,
        private Specification $inner,
    ) {}

    public function isSatisfiedBy(mixed $actual): bool
    {
        return $this->inner->isSatisfiedBy($this->actual);
    }
}
