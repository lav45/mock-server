<?php declare(strict_types=1);

namespace Lav45\MockServer\DataFactory\Condition\Specification;

use Lav45\MockServer\DataFactory\Condition\Specification;

final readonly class MatchesSpecification implements Specification
{
    private string $pattern;

    public function __construct(string $pattern)
    {
        $this->pattern = '~' . \str_replace('~', '\~', $pattern) . '~';
    }

    public function isSatisfiedBy(mixed $actual): bool
    {
        return \is_string($actual) && \preg_match($this->pattern, $actual) === 1;
    }
}
