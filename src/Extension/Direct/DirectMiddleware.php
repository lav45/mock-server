<?php declare(strict_types=1);

namespace Lav45\MockServer\Extension\Direct;

use Lav45\MockServer\Engine\Http\RequestHandler;
use Lav45\MockServer\Engine\Http\ServerRequest;
use Lav45\MockServer\Engine\Http\ServerResponse;
use Lav45\MockServer\Extension\Middleware;
use Lav45\MockServer\Parser\InlineParser;

final readonly class DirectMiddleware implements Middleware
{
    public function __construct(
        private DirectFactory $factory,
        private DirectHandler $handler,
    ) {}

    public function process(ServerRequest $request, RequestHandler $next): ServerResponse
    {
        $data = $request->getAttribute('data');
        if ($this->factory->has($data) === false) {
            return $next->handleRequest($request);
        }

        $data = $this->handler->request(
            $this->factory->create($request, $data),
        )->replace($data);

        /** @var InlineParser $parser */
        $parser = $request->getAttribute('parser');
        $data = $parser->replace($data);

        $request->setAttribute('data', $data);

        return $next->handleRequest($request);
    }
}
