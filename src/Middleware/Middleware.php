<?php declare(strict_types=1);

namespace Lav45\MockServer\Middleware;

use Lav45\MockServer\Engine\Http\ServerRequest;
use Lav45\MockServer\Engine\Http\ServerResponse;

interface Middleware
{
    public function process(ServerRequest $request, MiddlewareHandler $next): ServerResponse;
}
