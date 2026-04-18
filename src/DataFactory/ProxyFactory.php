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

    public function create(Request $request, VariableParser $parser, array $data): ProxyResponse
    {
        $factory = new DataBuilder($parser, $data);
        $requestAdapter = new RequestAdapter($request);

        $withJson = isset($data['content']) && \is_array($data['content']);
        $headers = $factory->createHeaders($withJson, $requestAdapter->getHeaders());

        if (isset($data['content'])) {
            $body = $factory->createBodyContent();
        } else {
            $body = Body::fromText($requestAdapter->getBody());
        }

        return new ProxyResponse(
            url: $factory->createUrl($requestAdapter->getQuery()),
            method: new HttpMethod($request->getMethod()),
            headers: $headers,
            body: $body,
        );
    }
}
