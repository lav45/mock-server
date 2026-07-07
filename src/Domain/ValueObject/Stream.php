<?php declare(strict_types=1);

namespace Lav45\MockServer\Domain\ValueObject;

interface Stream
{
    public function read(): string;
}
