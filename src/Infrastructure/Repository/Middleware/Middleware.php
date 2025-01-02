<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Repository\Middleware;

use Lav45\MockServer\Application\Query\Request\Request;
use Lav45\MockServer\Domain\Model\Response;

interface Middleware
{
    public function handle(array $data, Request $request, \Closure $next): Response;
}
