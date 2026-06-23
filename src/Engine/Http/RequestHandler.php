<?php declare(strict_types=1);

namespace Lav45\MockServer\Engine\Http;

interface RequestHandler
{
    public function handleRequest(ServerRequest $request): ServerResponse;
}
