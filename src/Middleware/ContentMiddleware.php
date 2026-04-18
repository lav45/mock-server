<?php declare(strict_types=1);

namespace Lav45\MockServer\Middleware;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Lav45\MockServer\DataFactory\ContentFactory;
use Lav45\MockServer\Responder\ContentResponder;

final readonly class ContentMiddleware
{
    public function __construct(
        private ContentFactory   $factory,
        private ContentResponder $responder,
    ) {}

    public function __invoke(Request $request, \Closure $next): Response
    {
        if ($request->getAttribute('responseType') !== ContentFactory::TYPE) {
            return $next($request);
        }
        return $this->responder->execute(
            $this->factory->create(
                parser: $request->getAttribute('parser'),
                data: $request->getAttribute('data')['response'] ?? [],
            ),
        );
    }
}
