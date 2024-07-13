<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Wrapper;

use Amp\Http\Client\HttpClient as Client;
use Amp\Http\Client\Request;
use Amp\Http\Client\Response;
use Amp\Http\HttpStatus;
use Closure;
use Psr\Log\LoggerInterface;

final class HttpClient
{
    private Closure|null $logMessage = null;

    public function __construct(
        private readonly Client               $client,
        private readonly LoggerInterface|null $logger = null,
        private readonly mixed                $logLevelOk = null,
        private readonly mixed                $logLevelError = null,
    ) {}

    public function request(
        string      $uri,
        string      $method = 'GET',
        string|null $body = null,
        array|null  $headers = null,
    ): Response {
        $request = new Request($uri, $method);

        $body && $request->setBody($body);
        $headers && $request->setHeaders($headers);

        $response = $this->client->request($request);

        $this->log($request, $response);

        return $response;
    }

    private function log(Request $request, Response $response): void
    {
        if ($this->logMessage === null ||
            $this->logger === null
        ) {
            return;
        }

        $message = \call_user_func($this->logMessage, $request, $response);

        $loggerLevel = match ($response->getStatus()) {
            HttpStatus::OK => $this->logLevelOk,
            default => $this->logLevelError,
        };

        $this->logger->log($loggerLevel, $message);
    }

    /**
     * @param Closure $message => fn (Request $request, Response $response): string { ... }
     */
    public function withLogMessage(Closure $message): self
    {
        $new = clone $this;
        $new->logMessage = $message;
        return $new;
    }
}
