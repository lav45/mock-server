<?php declare(strict_types=1);

namespace Lav45\MockServer\Middleware;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;

final readonly class MiddlewareChain implements MiddlewareHandler
{
    public function __construct(
        private Middleware        $middleware,
        private MiddlewareHandler $next,
    ) {}

    public function handle(Request $request): Response
    {
        return $this->middleware->process($request, $this->next);
    }
}
