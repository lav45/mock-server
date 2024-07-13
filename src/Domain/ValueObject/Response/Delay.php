<?php declare(strict_types=1);

namespace Lav45\MockServer\Domain\ValueObject\Response;

final readonly class Delay
{
    private function __construct(public float $value)
    {
        \assert($this->isValidDelay($value), 'Invalid delay');
    }

    public static function new(int|float|string $delay): self
    {
        return new self((float)$delay);
    }

    private function isValidDelay(float $delay): bool
    {
        return $delay >= 0.0;
    }
}
