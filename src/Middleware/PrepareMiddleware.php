<?php declare(strict_types=1);

namespace Lav45\MockServer\Middleware;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Lav45\MockServer\DataFactory\ParserFactory;

final readonly class PrepareMiddleware implements Middleware
{
    public function __construct(
        private ParserFactory $parserFactory,
    ) {}

    public function process(Request $request, MiddlewareHandler $next): Response
    {
        $data = $request->getAttribute('data');

        $parser = $this->parserFactory->create($request, $data['env'] ?? []);

        $request->setAttribute('data', $parser->replace($data));
        $request->setAttribute('parser', $parser);

        return $next->handle($request);
    }
}
