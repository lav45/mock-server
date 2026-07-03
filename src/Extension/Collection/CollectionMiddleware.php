<?php declare(strict_types=1);

namespace Lav45\MockServer\Extension\Collection;

use Lav45\MockServer\Engine\Http\RequestHandler;
use Lav45\MockServer\Engine\Http\ServerRequest;
use Lav45\MockServer\Engine\Http\ServerResponse;
use Lav45\MockServer\Extension\Middleware;

final readonly class CollectionMiddleware implements Middleware
{
    public function __construct(
        private CollectionFactory   $factory,
        private CollectionResponder $responder,
    ) {}

    public function process(ServerRequest $request, RequestHandler $next): ServerResponse
    {
        $response = $request->getAttribute('data')['response'] ?? [];
        if ($this->factory->has($response) === false) {
            return $next->handleRequest($request);
        }
        return $this->responder->execute(
            $this->factory->create(
                request: $request,
                parser: $request->getAttribute('parser'),
                data: $response,
            ),
        );
    }
}
