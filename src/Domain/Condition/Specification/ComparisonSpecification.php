<?php declare(strict_types=1);

namespace Lav45\MockServer\Domain\Condition\Specification;

use Lav45\MockServer\Domain\Condition\Specification;

final readonly class ComparisonSpecification implements Specification
{
    public function __construct(
        private mixed  $threshold,
        private string $operator,
    ) {}

    public function isSatisfiedBy(mixed $actual): bool
    {
        if (\is_numeric($actual) === false) {
            return false;
        }
        $value = (float)$actual;
        $threshold = (float)$this->threshold;
        return match ($this->operator) {
            '>' => $value > $threshold,
            '>=' => $value >= $threshold,
            '<' => $value < $threshold,
            '<=' => $value <= $threshold,
        };
    }
}
