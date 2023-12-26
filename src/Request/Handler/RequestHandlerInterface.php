<?php declare(strict_types=1);

namespace lav45\MockServer\Request\Handler;

use Amp\Http\Server\Response;
use lav45\MockServer\Request\Wrapper\RequestWrapper;

interface RequestHandlerInterface
{
    public function handleRequest(RequestWrapper $request): Response;
}