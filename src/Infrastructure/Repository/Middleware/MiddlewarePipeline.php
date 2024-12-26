<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Repository\Middleware;

use Closure;
use Lav45\MockServer\Application\Query\Request\Request;
use Lav45\MockServer\Domain\Model\Response;

final readonly class MiddlewarePipeline
{
    private array $middlewares;

    public function __construct(Middleware ...$middleware)
    {
        $this->middlewares = $middleware;
    }

    public function handle(array $data, Request $request): Response
    {
        $chain = \array_reduce(
            \array_reverse($this->middlewares),
            static function (Closure $next, Middleware $middleware): Closure {
                return static fn($data, Request $request): Response => $middleware->handle($data, $request, $next);
            },
            static function () {
                throw new \RuntimeException('Invalid middleware chain!');
            },
        );
        return $chain($data, $request);
    }
}
