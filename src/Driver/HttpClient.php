<?php declare(strict_types=1);

namespace Lav45\MockServer\Driver;

use Amp\Cancellation;
use Amp\Http\Client\DelegateHttpClient as Client;
use Amp\Http\Client\Request;
use Amp\NullCancellation;
use Lav45\MockServer\Engine\Http\ClientResponse;
use Lav45\MockServer\Engine\HttpClient as HttpClientInterface;

final class HttpClient implements HttpClientInterface
{
    private string|null $logLabel = null;

    public function __construct(
        private readonly Client       $client,
        private readonly Cancellation $cancellation = new NullCancellation(),
    ) {}

    public function withLabel(string $label): self
    {
        return clone($this, [
            'logLabel' => $label,
        ]);
    }

    public function request(
        string      $uri,
        string      $method = 'GET',
        array|null  $headers = null,
        string|null $body = null,
    ): ClientResponse {
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

        $response = $this->client->request($request, $this->cancellation);

        return new ClientResponse(
            status: $response->getStatus(),
            headers: $response->getHeaders(),
            body: $response->getBody()->buffer(),
        );
    }
}
