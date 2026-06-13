<?php declare(strict_types=1);

namespace Lav45\MockServer\Middleware;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;

final readonly class MiddlewarePipeline implements MiddlewareHandler
{
    private MiddlewareHandler $handler;

    public function __construct(Middleware ...$middleware)
    {
        $this->handler = \array_reduce(
            \array_reverse($middleware),
            static fn(MiddlewareHandler $next, Middleware $middleware): MiddlewareHandler => new MiddlewareChain($middleware, $next),
            new class implements MiddlewareHandler {
                public function handle(Request $request): Response
                {
                    throw new \RuntimeException('Invalid middleware chain!');
                }
            },
        );
    }

    public function handle(Request $request): Response
    {
        return $this->handler->handle($request);
    }
}
