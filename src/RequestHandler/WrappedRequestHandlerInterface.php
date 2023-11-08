<?php declare(strict_types=1);

namespace lav45\MockServer\RequestHandler;

use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use lav45\MockServer\Request\RequestWrapper;

interface WrappedRequestHandlerInterface extends RequestHandler
{
    public function handleWrappedRequest(RequestWrapper $request): Response;
}
