<?php declare(strict_types=1);

namespace Lav45\MockServer\Domain\Mock;

interface Throttling
{
    public function delay(): float;
}
