<?php declare(strict_types=1);

namespace Lav45\MockServer\DataFactory\Condition;

interface Specification
{
    public function isSatisfiedBy(mixed $actual): bool;
}
