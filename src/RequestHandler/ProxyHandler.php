<?php declare(strict_types=1);

namespace lav45\MockServer\RequestHandler;

use Amp\Http\Client\HttpContent;
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
            $uri = $this->getUri($this->parser, $this->proxy->url);
            $method = $request->getMethod();
            $query = $this->getQuery($this->parser, $uri->getQuery(), $request->get());
            $body = $this->getContent($this->parser, $this->proxy->content, $request->getContent());
            $headers = $this->getHeaders($this->parser, $this->proxy, $request->getHeaders());

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

    private function getUri(EnvParser $parser, string $url): Http
    {
        $url = $parser->replace($url);
        return Http::new($url);
    }

    private function getQuery(EnvParser $parser, string|null $uriQuery, array $requestQuery = []): array
    {
        if (empty($uriQuery)) {
            return $requestQuery;
        }
        $requestQuery = RequestWrapper::parseQuery($uriQuery) + $requestQuery;
        return $parser->replace($requestQuery);
    }

    private function getHeaders(EnvParser $parser, Proxy $proxy, array $requestHeaders): array
    {
        $result = $proxy->options['headers'] ?? $proxy->headers;
        $result += $this->filterHeaders($requestHeaders);
        return $parser->replace($result);
    }

    private function getContent(EnvParser $parser, array|string|null $optionalContent, HttpContent|null $requestContent): HttpContent|string
    {
        if (empty($optionalContent)) {
            return $requestContent ?: '';
        }
        $optionalContent = $parser->replace($optionalContent);
        if (is_array($optionalContent)) {
            $optionalContent = json_encode($optionalContent, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        return $optionalContent;
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