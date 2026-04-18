<?php declare(strict_types=1);

namespace Lav45\MockServer\Responder;

use Amp\Http\HttpStatus;
use Amp\Http\Server\Response;
use Lav45\MockServer\Domain\Response\ProxyResponse;

final readonly class ProxyResponder
{
    public function __construct(
        private HttpClient $httpClient,
    ) {}

    public function execute(ProxyResponse $data): Response
    {
        try {
            $response = $this->httpClient->request(
                uri: $data->url->value,
                method: $data->method->value,
                body: $data->body->toString(),
                headers: $data->headers->toArray(),
            );
        } // @codeCoverageIgnoreStart
        catch (\Throwable $exception) {
            return new Response(
                status: HttpStatus::INTERNAL_SERVER_ERROR,
                body: $exception->getMessage(),
            );
        } // @codeCoverageIgnoreEnd
        return new Response(
            status: $response->getStatus(),
            headers: $response->getHeaders(),
            body: $response->getBody(),
        );
    }
}
