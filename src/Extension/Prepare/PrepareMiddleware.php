<?php declare(strict_types=1);

namespace Lav45\MockServer\Extension\Prepare;

use Lav45\MockServer\Engine\Http\RequestHandler;
use Lav45\MockServer\Engine\Http\ServerRequest;
use Lav45\MockServer\Engine\Http\ServerResponse;
use Lav45\MockServer\Extension\Middleware;

final readonly class PrepareMiddleware implements Middleware
{
    public function __construct(
        private ParserFactory $parserFactory,
    ) {}

    public function process(ServerRequest $request, RequestHandler $next): ServerResponse
    {
        $data = $request->getAttribute('data');

        $parser = $this->parserFactory->create($request, $data['env'] ?? []);

        $request->setAttribute('data', $parser->replace($data));
        $request->setAttribute('parser', $parser);

        return $next->handleRequest($request);
    }
}
