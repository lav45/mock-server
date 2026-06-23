<?php declare(strict_types=1);

namespace Lav45\MockServer\DataFactory;

use Lav45\MockServer\Engine\Http\ServerRequest;
use Lav45\MockServer\Parser\VariableParser;

final readonly class ParserFactory
{
    public function __construct(
        private VariableParser $parser,
    ) {}

    public function create(ServerRequest $request, array $env): VariableParser
    {
        $requestAdapter = new RequestAdapter($request);

        return $this->parser->withData([
            'request' => [
                'method' => $request->getMethod(),
                'path' => $request->getPath(),
                'headers' => static fn() => $requestAdapter->getHeaders(),
                'params' => $request->getAttribute('params'),
                'query' => static fn() => $requestAdapter->getQuery(),
                'body' => static fn() => $requestAdapter->getData(),
                'rawBody' => static fn() => $requestAdapter->getBody(),
            ],
            'env' => $this->parser->replace($env),
        ]);
    }
}
