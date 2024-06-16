<?php declare(strict_types=1);

namespace lav45\MockServer\Infrastructure\Handler;

use function Amp\delay;

trait DelayTrait
{
    public function delay(float $start, float $delay): void
    {
        $end = \microtime(true);
        $timeout = $delay - ($end - $start);

        if ($timeout > 0.0) {
            delay($timeout);
        }
    }
}
