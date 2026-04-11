<?php declare(strict_types=1);

namespace Lav45\MockServer\Responder;

use function Amp\delay;

final readonly class Delay
{
    public function __construct(
        private float $start,
    ) {}

    public function wait(float $delay): void
    {
        if ($delay === 0.0) {
            return;
        }
        $end = \microtime(true);
        $timeout = $delay - ($end - $this->start);
        if ($timeout > 0.0) {
            delay($timeout);
        }
    }
}
