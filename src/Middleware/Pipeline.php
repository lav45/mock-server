<?php declare(strict_types=1);

namespace Lav45\MockServer\Middleware;

final readonly class Pipeline
{
    public static function create(callable ...$middleware): callable
    {
        return \array_reduce(
            \array_reverse($middleware),
            static function (callable|null $next, callable $middleware): callable {
                return static fn($request) => $middleware($request, $next);
            },
        );
    }
}
