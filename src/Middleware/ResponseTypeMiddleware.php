<?php declare(strict_types=1);

namespace Lav45\MockServer\Middleware;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;

final readonly class ResponseTypeMiddleware
{
    public function __construct(
        private string $default,
    ) {}

    public function __invoke(Request $request, \Closure $next): Response
    {
        $data = $request->getAttribute('data');

        $type = isset($data['response']['type'])
            ? \strtolower($data['response']['type'])
            : $this->default;

        $request->setAttribute('responseType', $type);

        return $next($request);
    }
}
