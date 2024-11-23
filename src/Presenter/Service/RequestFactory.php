<?php declare(strict_types=1);

namespace Lav45\MockServer\Presenter\Service;

use Amp\Http\Server\Request as HttpRequest;
use Lav45\MockServer\Application\Query\Request\Request as RequestData;
use Lav45\MockServer\Presenter\Service\Request as RequestWrapper;

final readonly class RequestFactory
{
    public static function create(HttpRequest $httpRequest): RequestData
    {
        $request = new RequestWrapper($httpRequest);

        return new RequestData(
            start: \microtime(true),
            method: $request->getMethod(),
            get: $request->get(),
            post: $request->post(),
            headers: $request->getHeaders(),
            urlParams: $request->getUrlParams(),
            body: $request->body(),
        );
    }
}
