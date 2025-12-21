<?php declare(strict_types=1);

namespace Lav45\MockServer\Domain\Model\Response;

final readonly class Start
{
    public function __construct(
        public float $value,
    ) {
        $this->isValidDelay($value) || throw new \InvalidArgumentException('Invalid start');
    }

    private function isValidDelay(float $start): bool
    {
        return $start >= 0.0;
    }
}
