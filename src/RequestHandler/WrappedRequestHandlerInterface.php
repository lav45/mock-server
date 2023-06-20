<?php

namespace lav45\MockServer\RequestHandler;

use Amp\Http\Server\Response;
use lav45\MockServer\Request\RequestWrapper;

interface WrappedRequestHandlerInterface
{
    public function handleWrappedRequest(RequestWrapper $request): Response;
}
