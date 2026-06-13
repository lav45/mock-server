<?php declare(strict_types=1);

namespace Lav45\MockServer\DataFactory\Condition\Specification;

use Lav45\MockServer\DataFactory\Condition\Specification;

final readonly class ComparisonSpecification implements Specification
{
    public function __construct(
        private float|int $threshold,
        private string    $operator,
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
