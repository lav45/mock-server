<?php declare(strict_types=1);

namespace Lav45\MockServer\DataFactory\Condition\Specification;

use Lav45\MockServer\DataFactory\Condition\Specification;

final readonly class InSpecification implements Specification
{
    public function __construct(private array $list) {}

    public function isSatisfiedBy(mixed $actual): bool
    {
        return \in_array($actual, $this->list, true);
    }
}
