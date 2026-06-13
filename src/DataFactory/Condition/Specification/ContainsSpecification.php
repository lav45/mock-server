<?php declare(strict_types=1);

namespace Lav45\MockServer\DataFactory\Condition\Specification;

use Lav45\MockServer\DataFactory\Condition\Specification;

final readonly class ContainsSpecification implements Specification
{
    public function __construct(private string $expected) {}

    public function isSatisfiedBy(mixed $actual): bool
    {
        if (\is_array($actual)) {
            return \in_array($this->expected, $actual, true);
        }
        return \is_string($actual) && \str_contains($actual, $this->expected);
    }
}
