<?php declare(strict_types=1);

namespace Lav45\MockServer\Http;

use Amp\Http\Server\Request;

final readonly class RequestDataFactory implements RequestDataFactoryInterface
{
    public function create(Request $request, array $urlParams = []): RequestData
    {
        $requestAdapter = new RequestAdapter($request);

        return new RequestData(
            method: $request->getMethod(),
            get: $requestAdapter->getQuery(),
            post: $requestAdapter->getData(),
            headers: $request->getHeaders(),
            urlParams: $urlParams,
            body: $requestAdapter->body,
        );
    }
}
