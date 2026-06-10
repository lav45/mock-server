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
                    // TODO deprecated
                    $this->logger->warning('The parameter "request.urlParams" is deprecated since 4.1.1 and will be removed in 5.0.0. Please run `bin/upgrade` to update your data.'); // @codeCoverageIgnore
                    return $params; // @codeCoverageIgnore
                },
                'get' => function () use ($requestAdapter) {
                    // TODO deprecated
                    $this->logger->warning('The parameter "request.get" is deprecated since 4.3.2 and will be removed in 5.0.0. Please run `bin/upgrade` to update your data.'); // @codeCoverageIgnore
                    return $requestAdapter->getQuery(); // @codeCoverageIgnore
                },
                'query' => static fn() => $requestAdapter->getQuery(),
                'post' => static fn() => $requestAdapter->getData(),
                'body' => function () use ($requestAdapter) {
                    // TODO deprecated
                    $this->logger->warning('The parameter "request.body" is deprecated since 4.3.2 and will be removed in 5.0.0. Please run `bin/upgrade` to update your data.'); // @codeCoverageIgnore
                    return $requestAdapter->getBody(); // @codeCoverageIgnore
                },
                'rawBody' => static fn() => $requestAdapter->getBody(),
            ],
            'env' => $this->parser->replace($env),
        ]);
    }
}
