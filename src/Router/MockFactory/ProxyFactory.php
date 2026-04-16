<?php declare(strict_types=1);

namespace Lav45\MockServer\Router\MockFactory;

use Amp\Http\Server\Request;
use Lav45\MockServer\Domain\Mock\Response;
use Lav45\MockServer\Domain\Mock\Response\Body;
use Lav45\MockServer\Domain\Mock\Response\HttpMethod;
use Lav45\MockServer\Http\RequestAdapter;
use Lav45\MockServer\Parser\VariableParser;

final readonly class ProxyFactory implements ResponseFactoryInterface
{
    public const string TYPE = 'proxy';

    public function create(VariableParser $parser, array $data, Request $request): Response
    {
        $factory = new AttributeBuilder($parser, $data);
        $requestAdapter = new RequestAdapter($request);

        $withJson = isset($data['content']) && \is_array($data['content']);
        $headers = $factory->createHeaders($withJson, $requestAdapter->getHeaders());

        if (isset($data['content'])) {
            $body = $factory->createBodyContent();
        } else {
            $body = Body::fromText($requestAdapter->getBody());
        }

        return new Response\ProxyResponse(
            delay: $factory->createDelay(),
            url: $factory->createUrl($requestAdapter->getQuery()),
            method: new HttpMethod($request->getMethod()),
            headers: $headers,
            body: $body,
        );
    }
}
