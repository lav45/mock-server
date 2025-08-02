<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Handler;

use Amp\Http\HttpStatus;
use Lav45\MockServer\Application\Query\Request\Response;
use Lav45\MockServer\Application\Query\Request\ResponseHandler;
use Lav45\MockServer\Domain\Model\Response\Proxy as ProxyEntity;
use Lav45\MockServer\Infrastructure\HttpClient\HttpClientInterface;

final readonly class Proxy implements ResponseHandler
{
    public function __construct(
        private ProxyEntity         $data,
        private HttpClientInterface $httpClient,
    ) {}

    public function execute(): Response
    {
        try {
            $response = $this->httpClient->request(
                uri: $this->data->url->value,
                method: $this->data->method->value,
                body: $this->data->body->toString(),
                headers: $this->data->headers->toArray(),
                logLabel: 'Proxy',
            );
            $responseBody = $response->getBody()->read() ?: '';
        } // @codeCoverageIgnoreStart
        catch (\Throwable $exception) {
            return new Response(
                status: HttpStatus::INTERNAL_SERVER_ERROR,
                body: $exception->getMessage(),
            );
        } // @codeCoverageIgnoreEnd

        DelayHelper::delay($this->data->start->value, $this->data->delay->value);

        return new Response(
            status: $response->getStatus(),
            headers: $response->getHeaders(),
            body: $responseBody,
        );
    }
}
