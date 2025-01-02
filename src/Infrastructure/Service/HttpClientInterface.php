<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Service;

use Amp\Http\Client\Response;

interface HttpClientInterface
{
    public function request(
        string      $uri,
        string      $method = 'GET',
        null|string $body = null,
        null|array  $headers = null,
    ): Response;

    /**
     * @param \Closure $message => fn (Request $request, Response $response): string { ... }
     */
    public function withLogMessage(\Closure $message): self;
}
