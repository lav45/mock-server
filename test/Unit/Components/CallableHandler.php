<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Components;

use Lav45\MockServer\Engine\Http\RequestHandler;
use Lav45\MockServer\Engine\Http\ServerRequest;
use Lav45\MockServer\Engine\Http\ServerResponse;

final readonly class CallableHandler implements RequestHandler
{
    private \Closure $handler;

    public function __construct(callable $handler)
    {
        $this->handler = $handler(...);
    }

    public function handleRequest(ServerRequest $request): ServerResponse
    {
        return ($this->handler)($request);
    }
}
