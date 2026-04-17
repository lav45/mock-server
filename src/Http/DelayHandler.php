<?php declare(strict_types=1);

namespace Lav45\MockServer\Http;

final readonly class DelayHandler
{
    public function start(): Delay
    {
        return new Delay(\microtime(true));
    }
}
