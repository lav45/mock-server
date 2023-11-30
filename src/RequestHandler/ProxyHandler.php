<?php declare(strict_types=1);

namespace lav45\MockServer\RequestHandler;

use Amp\Http\HttpStatus;
use Amp\Http\Server\Response;
use lav45\MockServer\EnvParser;
use lav45\MockServer\HttpClient;
use lav45\MockServer\Mock\Response\Proxy;
use lav45\MockServer\Request\RequestWrapper;
use League\Uri\Http;
use Throwable;

class ProxyHandler extends BaseRequestHandler
{
    public function __construct(
        private readonly Proxy      $proxy,
        private readonly EnvParser  $parser,
        private readonly HttpClient $httpClient,
    )
    {
    }

    public function handleWrappedRequest(RequestWrapper $request): Response
    {
        try {
            $url = $this->parser->replace($this->proxy->url);
            $uri = Http::new($url);

            $method = $request->getMethod();
            $query = $this->getQuery($uri->getQuery(), $request->get());
            $body = $request->getContent() ?: '';
            $headers = $this->getHeaders($this->proxy, $request->getHeaders());

            $response = $this->httpClient->request(
                uri: $uri,
                method: $method,
                query: $query,
                body: $body,
                headers: $headers,
            );
        } catch (Throwable $exception) {
            return new Response(
                status: HttpStatus::INTERNAL_SERVER_ERROR,
                body: $exception->getMessage()
            );
        }

        return new Response(
            $response->getStatus(),
            $response->getHeaders(),
            $response->getBody()->buffer()
        );
    }

    private function getQuery(string|null $uriQuery, array $requestQuery = []): array
    {
        if (empty($uriQuery)) {
            return $requestQuery;
        }
        return RequestWrapper::parseQuery($uriQuery) + $requestQuery;
    }

    private function getHeaders(Proxy $proxy, array $headers): array
    {
        $result = $proxy->options['headers'] ?? $proxy->headers;
        $result += $this->filterHeaders($headers);
        return $result;
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