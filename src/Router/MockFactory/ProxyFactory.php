<?php declare(strict_types=1);

namespace Lav45\MockServer\Router\MockFactory;

use Lav45\MockServer\Domain\Mock\Response;
use Lav45\MockServer\Domain\Mock\Response\Body;
use Lav45\MockServer\Domain\Mock\Response\HttpMethod;
use Lav45\MockServer\Http\RequestData;
use Lav45\MockServer\Parser\VariableParser;

final readonly class ProxyFactory implements ResponseFactoryInterface
{
    public const string TYPE = 'proxy';

    public function create(VariableParser $parser, array $data, RequestData $request): Response
    {
        $factory = new AttributeBuilder($parser, $data);

        $withJson = isset($data['content']) && \is_array($data['content']);
        $headers = $factory->createHeaders($withJson, $request->headers);

        if (isset($data['content'])) {
            $body = $factory->createBody();
        } else {
            $body = Body::fromText($request->body);
        }

        return new Response\Proxy(
            delay: $factory->createDelay(),
            url: $factory->createUrl($request->get),
            method: new HttpMethod($request->method),
            headers: $headers,
            body: $body,
        );
    }
}
