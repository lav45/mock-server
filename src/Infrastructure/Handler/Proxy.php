<?php declare(strict_types=1);

namespace lav45\MockServer\Infrastructure\Handler;

use Amp\Http\Client\Request as HttpRequest;
use Amp\Http\Client\Response as HttpResponse;
use Amp\Http\HttpStatus;
use lav45\MockServer\Application\DTO\Request;
use lav45\MockServer\Application\DTO\Response;
use lav45\MockServer\Application\Handler\Response as ResponseHandler;
use lav45\MockServer\Domain\Entity\Response\Proxy as ProxyEntity;
use lav45\MockServer\Infrastructure\Wrapper\HttpClient;
use Throwable;

final readonly class Proxy implements ResponseHandler
{
    use DelayTrait;

    private HttpClient $httpClient;

    public function __construct(
        private ProxyEntity $data,
        HttpClient          $httpClient,
    )
    {
        $this->httpClient = $httpClient->withLogMessage(static function (HttpRequest $request, HttpResponse $response) {
            return "Proxy: {$response->getStatus()} {$request->getMethod()} {$request->getUri()}";
        });
    }

    public function handle(Request $request): Response
    {
        $method = $request->method;
        $uri = $this->data->url->withQuery($request->get)->create()->value;
        $body = $this->data->content->toString() ?: $request->body;
        $headers = $this->data->headers->all() + $this->filterHeaders($request->headers);

        try {
            $response = $this->httpClient->request(
                uri: $uri,
                method: $method,
                body: $body,
                headers: $headers,
            );
        } // @codeCoverageIgnoreStart
        catch (Throwable $exception) {
            return new Response(
                status: HttpStatus::INTERNAL_SERVER_ERROR,
                body: $exception->getMessage()
            );
        } // @codeCoverageIgnoreEnd

        $responseBody = $response->getBody()->read() ?: '';

        $this->delay($request->start, $this->data->delay->value);

        return new Response(
            status: $response->getStatus(),
            headers: $response->getHeaders(),
            body: $responseBody,
        );
    }

    private function filterHeaders(array $headers): array
    {
        unset(
            $headers['host'],
            $headers['content-length'],
        );
        return $headers;
    }
}