<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\HttpClient;

use Amp\Http\Client\HttpClient as Client;
use Amp\Http\Client\Request;
use Amp\Http\Client\Response;

final readonly class HttpClient implements HttpClientInterface
{
    public function __construct(
        private Client $client,
    ) {}

    public function request(
        string      $uri,
        string      $method = 'GET',
        string|null $body = null,
        array|null  $headers = null,
        string|null $logLabel = null,
    ): Response {
        $request = new Request($uri, $method);

        if ($body) {
            $request->setBody($body);
        }
        if ($headers) {
            $request->setHeaders($headers);
        }
        if ($logLabel) {
            $request->setAttribute('logLabel', $logLabel);
        }

        return $this->client->request($request);
    }
}
