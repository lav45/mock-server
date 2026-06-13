<?php declare(strict_types=1);

namespace Lav45\MockServer\Middleware;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;

interface MiddlewareHandler
{
    public function handle(Request $request): Response;
}
