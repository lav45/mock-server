<?php declare(strict_types=1);

namespace Lav45\MockServer\Responder;

use Amp\Http\HttpStatus;
use Lav45\MockServer\Domain\Mock\Response;
use Lav45\MockServer\Domain\Mock\Response\Proxy as ProxyEntity;
use Lav45\MockServer\Http\ResponseData;
use Lav45\MockServer\Responder\HttpClient\HttpClientInterface;

final readonly class ProxyResponder implements ResponderInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
    ) {}

    public function execute(Response $data): ResponseData
    {
        if ($data instanceof ProxyEntity === false) {
            throw new \RuntimeException(\sprintf('Response data class %s is not allowed.', \get_class($data)));
        }

        try {
            $response = $this->httpClient->request(
                uri: $data->url->value,
                method: $data->method->value,
                body: $data->body->toString(),
                headers: $data->headers->toArray(),
            );
            $responseBody = $response->getBody()->buffer();
        } // @codeCoverageIgnoreStart
        catch (\Throwable $exception) {
            return new ResponseData(
                status: HttpStatus::INTERNAL_SERVER_ERROR,
                body: $exception->getMessage(),
            );
        } // @codeCoverageIgnoreEnd

        return new ResponseData(
            status: $response->getStatus(),
            headers: $response->getHeaders(),
            body: $responseBody,
        );
    }
}
