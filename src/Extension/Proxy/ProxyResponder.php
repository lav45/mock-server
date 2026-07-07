<?php declare(strict_types=1);

namespace Lav45\MockServer\Extension\Proxy;

use Lav45\MockServer\Domain\Response\ProxyResponse;
use Lav45\MockServer\Engine\Http\ServerResponse;
use Lav45\MockServer\Engine\HttpClient;

final readonly class ProxyResponder
{
    public function __construct(
        private HttpClient $httpClient,
    ) {}

    public function execute(ProxyResponse $data): ServerResponse
    {
        $response = $this->httpClient->request(
            uri: $data->url->value,
            method: $data->method->value,
            headers: $data->headers->toArray(),
            body: $data->body,
        );
        return new ServerResponse(
            status: $response->getStatus(),
            headers: $response->getHeaders(),
            body: $response->getBody(),
        );
    }
}
