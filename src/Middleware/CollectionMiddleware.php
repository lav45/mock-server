<?php declare(strict_types=1);

namespace Lav45\MockServer\Middleware;

use Lav45\MockServer\DataFactory\CollectionFactory;
use Lav45\MockServer\Engine\Http\ServerRequest;
use Lav45\MockServer\Engine\Http\ServerResponse;
use Lav45\MockServer\Responder\ContentResponder;

final readonly class CollectionMiddleware implements Middleware
{
    public function __construct(
        private CollectionFactory $factory,
        private ContentResponder $responder,
    ) {}

    public function process(ServerRequest $request, MiddlewareHandler $next): ServerResponse
    {
        $response = $request->getAttribute('data')['response'] ?? [];
        if ($this->factory->has($response) === false) {
            return $next->handle($request);
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
