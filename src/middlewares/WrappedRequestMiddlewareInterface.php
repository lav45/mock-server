<?php

namespace lav45\MockServer\middlewares;

use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use lav45\MockServer\Request\WrappedRequest;

interface WrappedRequestMiddlewareInterface
{
    public function handleWrappedRequest(WrappedRequest $request, RequestHandler $requestHandler): Response;
}
