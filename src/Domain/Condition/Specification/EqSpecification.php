<?php declare(strict_types=1);

namespace Lav45\MockServer\Domain\Condition\Specification;

use Lav45\MockServer\Domain\Condition\Specification;

final readonly class EqSpecification implements Specification
{
    public function __construct(private mixed $expected) {}

    public function isSatisfiedBy(mixed $actual): bool
    {
        return $actual === $this->expected;
    }
}
