<?php declare(strict_types=1);

namespace Lav45\MockServer\Responder;

use Amp\Http\HttpStatus;
use Lav45\MockServer\Domain\Direct;

final readonly class DirectHandler
{
    public function __construct(
        private HttpClient $httpClient,
    ) {}

    public function request(Direct $data): array
    {
        $response = $this->httpClient->request(
            uri: $data->url->value,
            method: $data->method->value,
            headers: $data->headers->toArray(),
            body: $data->body->value,
        );
        $body = $response->getBody()->buffer();
        if (\json_validate($body) && HttpStatus::isSuccessful($response->getStatus())) {
            return \json_decode($body, true, flags: JSON_THROW_ON_ERROR);
        }
        throw new \RuntimeException(message: $body, code: $response->getStatus());
    }
}
