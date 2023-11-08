<?php declare(strict_types=1);

namespace lav45\MockServer\middlewares;

use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use lav45\MockServer\Request\RequestWrapper;

abstract class BaseMiddleware implements Middleware, WrappedRequestMiddlewareInterface
{
    public function handleRequest(Request $request, RequestHandler $requestHandler): Response
    {
        $wrappedRequest = RequestWrapper::getInstance($request);
        return $this->handleWrappedRequest($wrappedRequest, $requestHandler);
    }
}
