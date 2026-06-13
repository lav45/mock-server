<?php declare(strict_types=1);

namespace Lav45\MockServer\Domain\Condition\Specification;

use Lav45\MockServer\Domain\Condition\Specification;
use Lav45\MockServer\Domain\ValueObject\Value;

final readonly class FieldSpecification implements Specification
{
    public function __construct(
        private mixed         $field,
        private Specification $inner,
    ) {}

    public function isSatisfiedBy(mixed $actual): bool
    {
        $value = \is_string($this->field) && \str_starts_with($this->field, '{{')
            ? Value::Undefined
            : $this->field;
        return $this->inner->isSatisfiedBy($value);
    }
}
