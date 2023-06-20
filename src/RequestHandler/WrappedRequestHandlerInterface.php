<?php

namespace lav45\MockServer\RequestHandler;

use Amp\Http\Server\Response;
use lav45\MockServer\Request\WrappedRequest;

interface WrappedRequestHandlerInterface
{
    public function handleWrappedRequest(WrappedRequest $request): Response;
}
