<?php declare(strict_types=1);

namespace Lav45\MockServer\Responder;

use Amp\Http\Server\Response;
use Lav45\MockServer\Domain\Response\ProxyResponse;

final readonly class ProxyResponder
{
    public function __construct(
        private HttpClient $httpClient,
    ) {}

    public function execute(ProxyResponse $data): Response
    {
        $response = $this->httpClient->request(
            uri: $data->url->value,
            method: $data->method->value,
            headers: $data->headers->toArray(),
            body: $data->body->toString(),
        );
        return new Response(
            status: $response->getStatus(),
            headers: $response->getHeaders(),
            body: $response->getBody(),
        );
    }
}
