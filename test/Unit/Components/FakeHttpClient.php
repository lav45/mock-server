<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Components;

use Lav45\MockServer\Domain\ValueObject\Body;
use Lav45\MockServer\Engine\Http\ClientResponse;
use Lav45\MockServer\Engine\HttpClient;

final class FakeHttpClient implements HttpClient
{
    /** @var list<array{uri: string, method: string, headers: array|null, body: Body|null}> */
    public array $calls = [];

    public function __construct(
        private int             $status = 200,
        private array           $headers = [],
        private string|array    $body = '',
        private \Throwable|null $exception = null,
    ) {}

    public function withLabel(string $label): self
    {
        return $this;
    }

    public function request(
        string     $uri,
        string     $method = 'GET',
        array|null $headers = null,
        Body|null  $body = null,
    ): ClientResponse {
        $this->calls[] = ['uri' => $uri, 'method' => $method, 'headers' => $headers, 'body' => $body];

        if ($this->exception !== null) {
            throw $this->exception;
        }
        return new ClientResponse($this->status, $this->headers, Body::new($this->body));
    }

    /** @return list<string> */
    public function uris(): array
    {
        return \array_column($this->calls, 'uri');
    }
}
