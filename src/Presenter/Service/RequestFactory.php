<?php declare(strict_types=1);

namespace Lav45\MockServer\Presenter\Service;

use Amp\Http\Server\Request as HttpRequest;
use Lav45\MockServer\Application\Query\Request\Request as RequestData;
use Lav45\MockServer\Presenter\Service\Request as RequestAdaptor;

final readonly class RequestFactory
{
    public static function create(HttpRequest $httpRequest, array $urlParams = []): RequestData
    {
        $request = new RequestAdaptor($httpRequest);

        return new RequestData(
            start: \microtime(true),
            method: $httpRequest->getMethod(),
            get: $request->query,
            post: $request->getData(),
            headers: $httpRequest->getHeaders(),
            urlParams: $urlParams,
            body: $request->body,
        );
    }
}
