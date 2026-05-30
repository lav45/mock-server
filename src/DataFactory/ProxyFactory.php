<?php declare(strict_types=1);

namespace Lav45\MockServer\DataFactory;

use Amp\Http\Server\Request;
use Lav45\MockServer\Domain\Response\ProxyResponse;
use Lav45\MockServer\Domain\ValueObject\Body;
use Lav45\MockServer\Domain\ValueObject\HttpMethod;
use Lav45\MockServer\Parser\VariableParser;

final readonly class ProxyFactory
{
    public const string TYPE = 'proxy';

    public function __construct(
        private array $filterHeaders = [],
    ) {}

    public function create(Request $request, VariableParser $parser, array $data): ProxyResponse
    {
        // TODO deprecated
        $withJson = isset($data['content']) && \is_array($data['content']);
        if ($withJson && isset($data['headers']['content-type']) === false) {
            $data['headers'] ??= [];
            $data['headers']['content-type'] = 'application/json';
        }

        $factory = new DataBuilder($parser, $data, $this->filterHeaders);
        $requestAdapter = new RequestAdapter($request);

        $url = $factory->createUrl($requestAdapter->getQuery());
        $method = new HttpMethod($request->getMethod());

        $headers = $factory->createHeaders($requestAdapter->getHeaders());

        $body = $factory->createBodyContent() ?? Body::new($requestAdapter->getBody());

        return new ProxyResponse(
            url: $url,
            method: $method,
            headers: $headers,
            body: $body,
        );
    }
}
