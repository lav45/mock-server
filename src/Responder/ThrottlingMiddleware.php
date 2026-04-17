<?php declare(strict_types=1);

namespace Lav45\MockServer\Responder;

use Amp\Http\Server\Response as HttpResponse;
use Lav45\MockServer\Domain\Mock\Response;
use Lav45\MockServer\Domain\Mock\Throttling;

use function Amp\delay;

final readonly class ThrottlingMiddleware
{
    public function __invoke(Response|Throttling $data, \Closure $next): HttpResponse
    {
        if ($data instanceof Throttling && $data->delay() > 0.0) {
            $delay = $data->delay();
        } else {
            return $next($data);
        }

        $start = \microtime(true);
        $response = $next($data);
        $end = \microtime(true);

        $timeout = $delay - ($end - $start);
        if ($timeout > 0.0) {
            delay($timeout);
        }
        return $response;
    }
}
