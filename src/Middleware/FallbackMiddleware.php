<?php declare(strict_types=1);

namespace Lav45\MockServer\Middleware;

use Amp\Http\HttpStatus;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final readonly class FallbackMiddleware
{
    public function __construct(
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    public function __invoke(Request $request): Response
    {
        $data = $request->getAttribute('data');
        $data = \json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $this->logger->error("Unresolved data: {$data}");
        return new Response(
            status: HttpStatus::NOT_FOUND,
        );
    }
}
