<?php declare(strict_types=1);

namespace Lav45\MockServer\DataFactory;

use Amp\Http\Server\Request;
use Lav45\MockServer\Parser\VariableParser;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final readonly class ParserFactory
{
    public function __construct(
        private VariableParser $parser,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    public function create(Request $request, array $env): VariableParser
    {
        $requestAdapter = new RequestAdapter($request);

        return $this->parser->withData([
            'request' => [
                'method' => $request->getMethod(),
                'headers' => static fn() => $requestAdapter->getHeaders(),
                'params' => $params = $request->getAttribute('params'),
                'urlParams' => function () use ($params) {
                    // TODO The parameter "request.urlParams" is deprecated since 4.1.1 and will be removed in 5.0.0. Please use "request.params" instead.
                    $this->logger->warning('The parameter "request.urlParams" is deprecated since 4.1.1 and will be removed in 5.0.0. Please run `bin/upgrade` to update your data.');
                    return $params;
                },
                'get' => static fn() => $requestAdapter->getQuery(),
                'post' => static fn() => $requestAdapter->getData(),
                'body' => static fn() => $requestAdapter->getBody(),
            ],
            'env' => $this->parser->replace($env),
        ]);
    }
}
