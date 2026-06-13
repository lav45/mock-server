<?php declare(strict_types=1);

namespace Lav45\MockServer\Domain\Condition\Specification;

use Lav45\MockServer\Domain\Condition\Specification;

final readonly class ContainsSpecification implements Specification
{
    public function __construct(private mixed $expected) {}

    public function isSatisfiedBy(mixed $actual): bool
    {
        $expected = $this->expected;
        if (\is_array($actual)) {
            return \in_array($expected, $actual, true);
        }
        return \is_string($actual) && \is_string($expected) && \str_contains($actual, $expected);
    }
}
