<?php declare(strict_types=1);

namespace Lav45\MockServer\Middleware;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Lav45\MockServer\DataFactory\ProxyFactory;
use Lav45\MockServer\Responder\ProxyResponder;

final readonly class ProxyMiddleware implements Middleware
{
    public function __construct(
        private ProxyFactory   $factory,
        private ProxyResponder $responder,
    ) {}

    public function process(Request $request, MiddlewareHandler $next): Response
    {
        $response = $request->getAttribute('data')['response'] ?? [];
        if ($this->factory->has($response) === false) {
            return $next->handle($request);
        }
        return $this->responder->execute(
            $this->factory->create(
                request: $request,
                data: $response,
            ),
        );
    }
}
