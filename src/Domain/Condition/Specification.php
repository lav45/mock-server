<?php declare(strict_types=1);

namespace Lav45\MockServer\Domain\Condition;

interface Specification
{
    public function isSatisfiedBy(mixed $actual): bool;
}
