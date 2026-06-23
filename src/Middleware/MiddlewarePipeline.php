<?php declare(strict_types=1);

namespace Lav45\MockServer\Middleware;

use Lav45\MockServer\Engine\Http\ServerRequest;
use Lav45\MockServer\Engine\Http\ServerResponse;

final readonly class MiddlewarePipeline implements MiddlewareHandler
{
    private MiddlewareHandler $handler;

    public function __construct(Middleware ...$middleware)
    {
        $this->handler = \array_reduce(
            \array_reverse($middleware),
            static fn(MiddlewareHandler $next, Middleware $middleware): MiddlewareHandler => new MiddlewareChain($middleware, $next),
            new class implements MiddlewareHandler {
                public function handle(ServerRequest $request): ServerResponse
                {
                    throw new \RuntimeException('Invalid middleware chain!');
                }
            },
        );
    }

    public function handle(ServerRequest $request): ServerResponse
    {
        return $this->handler->handle($request);
    }
}
