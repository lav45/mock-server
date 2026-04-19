<?php declare(strict_types=1);

namespace Lav45\MockServer\Responder\HttpClient;

use Amp\Http\Client\HttpClient as Client;
use Amp\Http\Client\Request;
use Amp\Http\Client\Response;

final class HttpClient implements \Lav45\MockServer\Responder\HttpClient
{
    private string|null $logLabel = null;

    public function __construct(
        private readonly Client $client,
    ) {}

    public function withLabel(string $label): self
    {
        $new = clone $this;
        $new->logLabel = $label;
        return $new;
    }

    public function request(
        string      $uri,
        string      $method = 'GET',
        array|null  $headers = null,
        string|null $body = null,
    ): Response {
        $request = new Request($uri, $method);

        if ($body) {
            $request->setBody($body);
        }
        if ($headers) {
            $request->setHeaders($headers);
        }
        if ($this->logLabel) {
            $request->setAttribute('logLabel', $this->logLabel);
        }
        return $this->client->request($request);
    }
}
