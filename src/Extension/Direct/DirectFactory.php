<?php declare(strict_types=1);

namespace Lav45\MockServer\Extension\Direct;

use Lav45\MockServer\DataFactory\DataBuilder;
use Lav45\MockServer\DataFactory\RequestAdapter;
use Lav45\MockServer\Domain\Direct;
use Lav45\MockServer\Domain\ValueObject\Body;
use Lav45\MockServer\Domain\ValueObject\HttpMethod;
use Lav45\MockServer\Engine\Http\ServerRequest;

final readonly class DirectFactory
{
    private const string TYPE = 'direct';

    public function __construct(
        private DataBuilder $dataBuilder,
    ) {}

    public function has(array $data): bool
    {
        return isset($data[self::TYPE]);
    }

    public function create(ServerRequest $request, array $data): Direct
    {
        $factory = $this->dataBuilder->withData($data[self::TYPE]);
        $requestAdapter = new RequestAdapter($request);

        return new Direct(
            url: $factory->createUrl($requestAdapter->getQuery()),
            method: new HttpMethod($request->getMethod()),
            headers: $factory->createHeaders($requestAdapter->getHeaders()),
            body: Body::new($requestAdapter->getBody()),
        );
    }
}
