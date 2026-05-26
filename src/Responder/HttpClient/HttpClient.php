<?php declare(strict_types=1);

namespace Lav45\MockServer\Responder\HttpClient;

use Amp\Cancellation;
use Amp\Http\Client\DelegateHttpClient as Client;
use Amp\Http\Client\Request;
use Amp\Http\Client\Response;
use Amp\NullCancellation;

final class HttpClient implements \Lav45\MockServer\Responder\HttpClient
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
        return $this->client->request($request, $this->cancellation);
    }
}
