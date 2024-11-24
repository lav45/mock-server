<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Handler;

use function Amp\delay;

final readonly class DelayHelper
{
    public static function delay(float $start, float $delay): void
    {
        if ($delay === 0.0) {
            return;
        }
        $end = \microtime(true);
        $timeout = $delay - ($end - $start);

        if ($timeout > 0.0) {
            delay($timeout);
        }
    }
}
