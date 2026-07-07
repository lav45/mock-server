<?php declare(strict_types=1);

namespace Lav45\MockServer\Domain\ValueObject;

final readonly class StringStream implements Stream
{
    public function __construct(
        private string $value,
    ) {}

    public function read(): string
    {
        return $this->value;
    }
}
