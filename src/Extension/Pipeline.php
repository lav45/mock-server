<?php declare(strict_types=1);

namespace Lav45\MockServer\Extension;

use Lav45\MockServer\Engine\Http\RequestHandler;
use Lav45\MockServer\Engine\Http\ServerRequest;
use Lav45\MockServer\Engine\Http\ServerResponse;

final readonly class Pipeline implements RequestHandler
{
    private RequestHandler $handler;

    public function __construct(Middleware ...$middleware)
    {
        $this->handler = \array_reduce(
            \array_reverse($middleware),
            static fn(RequestHandler $next, Middleware $middleware): RequestHandler => new readonly class ($middleware, $next) implements RequestHandler {
                public function __construct(
                    private Middleware     $middleware,
                    private RequestHandler $next,
                ) {}

                public function handleRequest(ServerRequest $request): ServerResponse
                {
                    return $this->middleware->process($request, $this->next);
                }
            },
            new class implements RequestHandler {
                public function handleRequest(ServerRequest $request): ServerResponse
                {
                    throw new \RuntimeException('Invalid middleware chain!');
                }
            },
        );
    }

    public function handleRequest(ServerRequest $request): ServerResponse
    {
        return $this->handler->handleRequest($request);
    }
}
