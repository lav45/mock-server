<?php declare(strict_types=1);

namespace Lav45\MockServer\Middleware;

use Lav45\MockServer\Engine\Http\ServerRequest;
use Lav45\MockServer\Engine\Http\ServerResponse;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final readonly class FallbackMiddleware implements Middleware
{
    public function __construct(
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    public function process(ServerRequest $request, MiddlewareHandler $next): ServerResponse
    {
        $data = $request->getAttribute('data');
        $data = \json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $this->logger->error("Unresolved mock: {$data}");
        return new ServerResponse(
            status: 404,
        );
    }
}
