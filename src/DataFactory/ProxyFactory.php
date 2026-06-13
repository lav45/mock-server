<?php declare(strict_types=1);

namespace Lav45\MockServer\DataFactory;

use Amp\Http\Server\Request;
use Lav45\MockServer\Domain\Response\ProxyResponse;
use Lav45\MockServer\Domain\ValueObject\Body;
use Lav45\MockServer\Domain\ValueObject\HttpMethod;

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

    public function create(Request $request, array $data): ProxyResponse
    {
        $factory = $this->dataBuilder->withData($data);
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
