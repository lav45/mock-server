<?php declare(strict_types=1);

namespace Lav45\MockServer\Domain\Condition\Specification;

use Lav45\MockServer\Domain\Condition\Specification;

final readonly class MatchesSpecification implements Specification
{
    public function __construct(private mixed $pattern) {}

    public function isSatisfiedBy(mixed $actual): bool
    {
        if (\is_string($actual) === false) {
            return false;
        }
        $pattern = '~' . \str_replace('~', '\~', $this->pattern) . '~';
        return \preg_match($pattern, $actual) === 1;
    }
}
