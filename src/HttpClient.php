<?php declare(strict_types=1);

namespace lav45\MockServer;

use Amp\Http\Client\Connection\DefaultConnectionFactory;
use Amp\Http\Client\Connection\UnlimitedConnectionPool;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\HttpContent;
use Amp\Http\Client\Request;
use Amp\Http\Client\Response;
use Amp\Http\HttpStatus;
use Amp\Socket\ClientTlsContext;
use Amp\Socket\ConnectContext;
use Closure;
use Psr\Http\Message\UriInterface;
use Psr\Log\LoggerInterface;

final class HttpClient
{
    private \Amp\Http\Client\HttpClient $client;

    private Closure|null $logMessage = null;

    public function __construct(
        private readonly LoggerInterface|null $logger = null,
        private readonly mixed                $logLevelOk = null,
        private readonly mixed                $logLevelError = null,
    )
    {
    }

    public function build(): self
    {
        $this->client = $this->getBuilder()->build();
        return $this;
    }

    protected function getBuilder(): HttpClientBuilder
    {
        $tls = (new ClientTlsContext(''))
            ->withoutPeerVerification()
            ->withSecurityLevel(0);

        $context = (new ConnectContext)
            ->withTlsContext($tls);

        $factory = new DefaultConnectionFactory(null, $context);

        $pool = new UnlimitedConnectionPool($factory);

        return (new HttpClientBuilder())
            ->usingPool($pool);
    }

    public function request(
        UriInterface|string $uri,
        string              $method = 'GET',
        array               $query = [],
        HttpContent|string  $body = '',
        array               $headers = []
    ): Response
    {
        $request = new Request($uri, $method);
        $request->setBody($body);
        $request->setQueryParameters($query);
        $request->setHeaders($headers);

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
        $message = call_user_func($this->logMessage, $request, $response);
        $loggerLevel = match ($response->getStatus()) {
            HttpStatus::OK => $this->logLevelOk,
            default => $this->logLevelError
        };

        $this->logger->log($loggerLevel, $message);
    }

    /**
     * @param Closure $message => fn (Request $request, Response $response): string { ... }
     * @return self
     */
    public function withLogMessage(Closure $message): self
    {
        $new = clone $this;
        $new->logMessage = $message;
        return $new;
    }
}