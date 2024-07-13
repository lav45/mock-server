<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Factory;

use Amp\Http\Server\Request as HttpRequest;
use Lav45\MockServer\Application\Data\Request as RequestData;
use Lav45\MockServer\Infrastructure\Wrapper\Request as RequestWrapper;

final readonly class Request
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
