<?php declare(strict_types=1);

namespace Lav45\MockServer\Domain\Condition\Specification;

use Lav45\MockServer\Domain\Condition\Specification;

final readonly class OrSpecification implements Specification
{
    /** @var Specification[] */
    private array $specs;

    public function __construct(Specification ...$specs)
    {
        $this->specs = $specs;
    }

    public function isSatisfiedBy(mixed $actual): bool
    {
        return \array_any($this->specs, static fn(Specification $spec) => $spec->isSatisfiedBy($actual));
    }
}
