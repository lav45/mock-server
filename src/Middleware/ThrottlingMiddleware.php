<?php declare(strict_types=1);

namespace Lav45\MockServer\Middleware;

use Lav45\MockServer\DataFactory\DataBuilder;
use Lav45\MockServer\Engine\Http\ServerRequest;
use Lav45\MockServer\Engine\Http\ServerResponse;

final readonly class ThrottlingMiddleware implements Middleware
{
    public function __construct(
        private DataBuilder $dataBuilder,
    ) {}

    public function process(ServerRequest $request, MiddlewareHandler $next): ServerResponse
    {
        $data = $request->getAttribute('data')['response'] ?? [];
        if (isset($data['delay']) === false) {
            return $next->handle($request);
        }

        $factory = $this->dataBuilder->withData($data);
        $delay = $factory->createDelay()->value;
        if ($delay === 0.0) {
            return $next->handle($request);
        }

        $start = \microtime(true);
        $response = $next->handle($request);
        $end = \microtime(true);

        $timeout = $delay - ($end - $start);
        if ($timeout > 0.0) {
            \usleep((int)($timeout * 1_000_000));
        }
        return $response;
    }
}
