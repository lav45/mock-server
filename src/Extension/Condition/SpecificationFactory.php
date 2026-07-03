<?php declare(strict_types=1);

namespace Lav45\MockServer\Extension\Condition;

use Lav45\MockServer\Domain\Condition\Specification;
use Lav45\MockServer\Domain\Condition\Specification\AlwaysSpecification;
use Lav45\MockServer\Domain\Condition\Specification\AndSpecification;
use Lav45\MockServer\Domain\Condition\Specification\ComparisonSpecification;
use Lav45\MockServer\Domain\Condition\Specification\ContainsSpecification;
use Lav45\MockServer\Domain\Condition\Specification\EmptySpecification;
use Lav45\MockServer\Domain\Condition\Specification\EqSpecification;
use Lav45\MockServer\Domain\Condition\Specification\ExistsSpecification;
use Lav45\MockServer\Domain\Condition\Specification\FieldSpecification;
use Lav45\MockServer\Domain\Condition\Specification\InSpecification;
use Lav45\MockServer\Domain\Condition\Specification\MatchesSpecification;
use Lav45\MockServer\Domain\Condition\Specification\NeverSpecification;
use Lav45\MockServer\Domain\Condition\Specification\NormalizedActualSpecification;
use Lav45\MockServer\Domain\Condition\Specification\NotSpecification;
use Lav45\MockServer\Domain\Condition\Specification\OrSpecification;

final readonly class SpecificationFactory
{
    public function create(array $expression): Specification
    {
        if ($expression === []) {
            return new AlwaysSpecification();
        }
        $operator = \strtolower($expression[0]);
        if ($operator === 'and') {
            return new AndSpecification(...$this->createAll($expression));
        }
        if ($operator === 'or') {
            return new OrSpecification(...$this->createAll($expression));
        }
        if ($operator === 'not') {
            return new NotSpecification($this->create($expression[1]));
        }
        if ($operator === 'exists') {
            return new FieldSpecification(
                $expression[1],
                new ExistsSpecification(),
            );
        }
        if ($operator === 'empty') {
            return new FieldSpecification(
                $expression[1],
                new EmptySpecification(),
            );
        }
        if (\count($expression) >= 3) {
            return new FieldSpecification(
                $expression[1],
                $this->build($operator, $expression[2]),
            );
        }
        return new NeverSpecification();
    }

    private function createAll(array $expressions): array
    {
        $result = [];
        foreach (\array_slice($expressions, 1) as $expression) {
            $result[] = $this->create($expression);
        }
        return $result;
    }

    public function build(string $operator, mixed $expected): Specification
    {
        $spec = match ($operator) {
            '=' => new EqSpecification($expected),
            '!=' => new NotSpecification(new EqSpecification($expected)),
            '>' => new ComparisonSpecification($expected, '>'),
            '>=' => new ComparisonSpecification($expected, '>='),
            '<' => new ComparisonSpecification($expected, '<'),
            '<=' => new ComparisonSpecification($expected, '<='),
            'contains' => new ContainsSpecification($expected),
            '~' => new MatchesSpecification($expected),
            'in' => new InSpecification($expected),
            default => new NeverSpecification(),
        };
        return new NormalizedActualSpecification($spec);
    }
}
