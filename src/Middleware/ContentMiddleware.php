<?php declare(strict_types=1);

namespace Lav45\MockServer\Middleware;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Lav45\MockServer\DataFactory\ContentFactory;
use Lav45\MockServer\Responder\ContentResponder;

final readonly class ContentMiddleware implements Middleware
{
    public function __construct(
        private ContentFactory   $factory,
        private ContentResponder $responder,
    ) {}

    public function process(Request $request, MiddlewareHandler $next): Response
    {
        $response = $request->getAttribute('data')['response'] ?? [];
        if ($this->factory->has($response) === false) {
            return $next->handle($request);
        }
        return $this->responder->execute(
            $this->factory->create($response),
        );
    }
}
