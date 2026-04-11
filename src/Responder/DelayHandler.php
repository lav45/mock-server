<?php declare(strict_types=1);

namespace Lav45\MockServer\Responder;

final readonly class DelayHandler implements DelayHandlerInterface
{
    public function start(): Delay
    {
        return new Delay(\microtime(true));
    }
}
