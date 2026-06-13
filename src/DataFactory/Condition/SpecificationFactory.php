<?php declare(strict_types=1);

namespace Lav45\MockServer\DataFactory\Condition;

use Lav45\MockServer\DataFactory\Condition\Specification\AndSpecification;
use Lav45\MockServer\DataFactory\Condition\Specification\ComparisonSpecification;
use Lav45\MockServer\DataFactory\Condition\Specification\ContainsSpecification;
use Lav45\MockServer\DataFactory\Condition\Specification\EmptySpecification;
use Lav45\MockServer\DataFactory\Condition\Specification\EqSpecification;
use Lav45\MockServer\DataFactory\Condition\Specification\ExistsSpecification;
use Lav45\MockServer\DataFactory\Condition\Specification\FieldSpecification;
use Lav45\MockServer\DataFactory\Condition\Specification\InSpecification;
use Lav45\MockServer\DataFactory\Condition\Specification\MatchesSpecification;
use Lav45\MockServer\DataFactory\Condition\Specification\NeverSpecification;
use Lav45\MockServer\DataFactory\Condition\Specification\NormalizedActualSpecification;
use Lav45\MockServer\DataFactory\Condition\Specification\NotSpecification;
use Lav45\MockServer\DataFactory\Condition\Specification\OrSpecification;
use Lav45\MockServer\Domain\ValueObject\Value;
use Lav45\MockServer\Parser\InlineParser;

final readonly class SpecificationFactory
{
    public function create(array $expression, InlineParser $parser): Specification
    {
        $operator = \strtolower($expression[0]);
        if ($operator === 'and') {
            return new AndSpecification(...\array_map(
                fn(array $expression) => $this->create($expression, $parser),
                \array_slice($expression, 1),
            ));
        }
        if ($operator === 'or') {
            return new OrSpecification(...\array_map(
                fn(array $expression) => $this->create($expression, $parser),
                \array_slice($expression, 1),
            ));
        }
        if ($operator === 'not') {
            return new NotSpecification(
                $this->create($expression[1], $parser),
            );
        }
        if ($operator === 'exists') {
            return new FieldSpecification(
                $this->resolveField($expression[1], $parser),
                new ExistsSpecification(),
            );
        }
        if ($operator === 'empty') {
            return new FieldSpecification(
                $this->resolveField($expression[1], $parser),
                new EmptySpecification(),
            );
        }
        if (\count($expression) >= 3) {
            return new FieldSpecification(
                $this->resolveField($expression[0], $parser),
                $this->build($expression[1], $parser->replace($expression[2])),
            );
        }
        return new NeverSpecification();
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

    private function resolveField(string $field, InlineParser $parser): mixed
    {
        $template = '{{' . $field . '}}';
        $result = $parser->replace($template);
        return $result === $template ? Value::Undefined : $result;
    }
}
