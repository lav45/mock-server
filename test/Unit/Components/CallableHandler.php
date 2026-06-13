<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Components;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Lav45\MockServer\Middleware\MiddlewareHandler;

final readonly class CallableHandler implements MiddlewareHandler
{
    private \Closure $handler;

    public function __construct(callable $handler)
    {
        $this->handler = $handler(...);
    }

    public function handle(Request $request): Response
    {
        return ($this->handler)($request);
    }
}
