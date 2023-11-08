<?php declare(strict_types=1);

namespace lav45\MockServer\RequestHandler;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use lav45\MockServer\Request\RequestWrapper;

abstract class BaseRequestHandler implements WrappedRequestHandlerInterface
{
    public function handleRequest(Request $request): Response
    {
        $wrappedRequest = RequestWrapper::getInstance($request);
        return $this->handleWrappedRequest($wrappedRequest);
    }
}
