<?php declare(strict_types=1);

namespace Lav45\MockServer\Extension\Proxy;

use Lav45\MockServer\DataFactory\DataBuilder;
use Lav45\MockServer\DataFactory\RequestAdapter;
use Lav45\MockServer\Domain\Response\ProxyResponse;
use Lav45\MockServer\Domain\ValueObject\Body;
use Lav45\MockServer\Domain\ValueObject\HttpMethod;
use Lav45\MockServer\Engine\Http\ServerRequest;

final readonly class ProxyFactory
{
    private const string TYPE = 'proxy';

    public function __construct(
        private DataBuilder $dataBuilder,
    ) {}

    public function has(array $data): bool
    {
        return isset($data['type']) && $data['type'] === self::TYPE;
    }

    public function create(ServerRequest $request, array $data): ProxyResponse
    {
        $factory = $this->dataBuilder->withData($data);
        $requestAdapter = new RequestAdapter($request);

        $url = $factory->createUrl($requestAdapter->getQuery());
        $method = new HttpMethod($request->getMethod());

        $body = $factory->createBodyContent() ?? Body::new($requestAdapter->getBody());

        $headers = $factory->createHeaders(
            $requestAdapter->getHeaders(),
        );

        return new ProxyResponse(
            url: $url,
            method: $method,
            headers: $headers,
            body: $body,
        );
    }
}
