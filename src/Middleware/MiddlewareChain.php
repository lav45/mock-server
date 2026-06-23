<?php declare(strict_types=1);

namespace Lav45\MockServer\Middleware;

use Lav45\MockServer\Engine\Http\ServerRequest;
use Lav45\MockServer\Engine\Http\ServerResponse;

final readonly class MiddlewareChain implements MiddlewareHandler
{
    public function __construct(
        private Middleware        $middleware,
        private MiddlewareHandler $next,
    ) {}

    public function handle(ServerRequest $request): ServerResponse
    {
        return $this->middleware->process($request, $this->next);
    }
}
