<?php declare(strict_types=1);

namespace Lav45\MockServer\Domain\Condition\Specification;

use Lav45\MockServer\Domain\Condition\Specification;

final readonly class InSpecification implements Specification
{
    public function __construct(private mixed $list) {}

    public function isSatisfiedBy(mixed $actual): bool
    {
        return \is_array($this->list) && \in_array($actual, $this->list, true);
    }
}
