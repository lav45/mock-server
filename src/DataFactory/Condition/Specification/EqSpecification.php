<?php declare(strict_types=1);

namespace Lav45\MockServer\DataFactory\Condition\Specification;

use Lav45\MockServer\DataFactory\Condition\Specification;

final readonly class EqSpecification implements Specification
{
    public function __construct(private mixed $expected) {}

    public function isSatisfiedBy(mixed $actual): bool
    {
        if (\is_int($this->expected)
            && \is_string($actual)
            && \is_numeric($actual)
            && \str_contains($actual, '.') === false) {
            $actual = (int)$actual;
        }
        return $actual === $this->expected;
    }
}
