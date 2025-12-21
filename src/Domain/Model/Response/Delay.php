<?php declare(strict_types=1);

namespace Lav45\MockServer\Domain\Model\Response;

final readonly class Delay
{
    public function __construct(
        public float $value,
    ) {
        $this->isValidDelay($value) || throw new \InvalidArgumentException('Invalid delay');
    }

    private function isValidDelay(float $delay): bool
    {
        return $delay >= 0.0;
    }
}
