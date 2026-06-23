<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Components;

use Lav45\MockServer\Engine\Http\ServerRequest;
use Lav45\MockServer\Engine\Http\ServerResponse;
use Lav45\MockServer\Middleware\MiddlewareHandler;

final readonly class CallableHandler implements MiddlewareHandler
{
    private \Closure $handler;

    public function __construct(callable $handler)
    {
        $this->handler = $handler(...);
    }

    public function handle(ServerRequest $request): ServerResponse
    {
        return ($this->handler)($request);
    }
}
