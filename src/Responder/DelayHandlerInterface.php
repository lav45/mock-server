<?php declare(strict_types=1);

namespace Lav45\MockServer\Responder;

interface DelayHandlerInterface
{
    public function start(): Delay;
}
