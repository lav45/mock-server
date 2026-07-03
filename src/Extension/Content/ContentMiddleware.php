<?php declare(strict_types=1);

namespace Lav45\MockServer\Extension\Content;

use Lav45\MockServer\Engine\Http\RequestHandler;
use Lav45\MockServer\Engine\Http\ServerRequest;
use Lav45\MockServer\Engine\Http\ServerResponse;
use Lav45\MockServer\Extension\Middleware;

final readonly class ContentMiddleware implements Middleware
{
    public function __construct(
        private ContentFactory   $factory,
        private ContentResponder $responder,
    ) {}

    public function process(ServerRequest $request, RequestHandler $next): ServerResponse
    {
        $response = $request->getAttribute('data')['response'] ?? [];
        if ($this->factory->has($response) === false) {
            return $next->handleRequest($request);
        }
        return $this->responder->execute(
            $this->factory->create($response),
        );
    }
}
