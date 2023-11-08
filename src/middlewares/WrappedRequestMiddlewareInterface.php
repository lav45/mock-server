<?php declare(strict_types=1);

namespace lav45\MockServer\middlewares;

use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use lav45\MockServer\Request\RequestWrapper;

interface WrappedRequestMiddlewareInterface
{
    public function handleWrappedRequest(RequestWrapper $request, RequestHandler $requestHandler): Response;
}
