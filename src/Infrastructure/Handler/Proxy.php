<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Handler;

use Amp\Http\Client\Request as HttpRequest;
use Amp\Http\Client\Response as HttpResponse;
use Amp\Http\HttpStatus;
use Lav45\MockServer\Application\Query\Request\Response;
use Lav45\MockServer\Application\Query\Request\ResponseHandler;
use Lav45\MockServer\Domain\Model\Response\Proxy as ProxyEntity;
use Lav45\MockServer\Infrastructure\Service\HttpClientInterface;
use Throwable;

final readonly class Proxy implements ResponseHandler
{
    private HttpClientInterface $httpClient;

    public function __construct(
        private ProxyEntity $data,
        HttpClientInterface $httpClient,
    ) {
        $this->httpClient = $httpClient->withLogMessage(static function (HttpRequest $request, HttpResponse $response) {
            return "Proxy: {$response->getStatus()} {$request->getMethod()} {$request->getUri()}";
        });
    }

    public function execute(): Response
    {
        try {
            $response = $this->httpClient->request(
                uri: $this->data->url->value,
                method: $this->data->method->value,
                body: $this->data->body->toString(),
                headers: $this->data->headers->toArray(),
            );
            $responseBody = $response->getBody()->read() ?: '';
        } // @codeCoverageIgnoreStart
        catch (Throwable $exception) {
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
