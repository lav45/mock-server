<?php declare(strict_types=1);

namespace Lav45\MockServer\Middleware;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Lav45\MockServer\DataFactory\ProxyFactory;
use Lav45\MockServer\Responder\ProxyResponder;

final readonly class ProxyMiddleware
{
    public function __construct(
        private ProxyFactory   $factory,
        private ProxyResponder $responder,
    ) {}

    public function __invoke(Request $request, \Closure $next): Response
    {
        if ($request->getAttribute('responseType') !== ProxyFactory::TYPE) {
            return $next($request);
        }
        return $this->responder->execute(
            $this->factory->create(
                request: $request,
                parser: $request->getAttribute('parser'),
                data: $request->getAttribute('data')['response'] ?? [],
            ),
        );
    }
}
