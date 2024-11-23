<?php declare(strict_types=1);

namespace Lav45\MockServer\Domain\Model\Response;

final readonly class Start
{
    public function __construct(
        public float $value,
    ) {
        \assert($this->isValidDelay($value), 'Invalid start');
    }

    private function isValidDelay(float $start): bool
    {
        return $start >= 0.0;
    }
}
