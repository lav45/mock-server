<?php declare(strict_types=1);

namespace Lav45\MockServer\Middleware;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Lav45\MockServer\DataFactory\DataBuilder;

use function Amp\delay;

final readonly class ThrottlingMiddleware
{
    public function __invoke(Request $request, \Closure $next): Response
    {
        $data = $request->getAttribute('data')['response'] ?? [];
        if (isset($data['delay']) === false) {
            return $next($request);
        }

        $factory = new DataBuilder(
            parser: $request->getAttribute('parser'),
            data: $data,
        );
        $delay = $factory->createDelay()->value;
        if ($delay === 0.0) {
            return $next($request);
        }

        $start = \microtime(true);
        $response = $next($request);
        $end = \microtime(true);

        $timeout = $delay - ($end - $start);
        if ($timeout > 0.0) {
            delay($timeout);
        }
        return $response;
    }
}
