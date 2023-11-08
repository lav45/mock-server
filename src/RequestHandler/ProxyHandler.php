<?php declare(strict_types=1);

namespace lav45\MockServer\RequestHandler;

use Amp\Http\HttpStatus;
use Amp\Http\Server\Response;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\RequestOptions;
use lav45\MockServer\EnvParser;
use lav45\MockServer\Mock\Response\Proxy;
use lav45\MockServer\Request\RequestWrapper;

class ProxyHandler extends BaseRequestHandler
{
    public function __construct(
        private readonly Proxy     $proxy,
        private readonly EnvParser $parser
    )
    {
    }

    public function handleWrappedRequest(RequestWrapper $request): Response
    {
        $url = $this->parser->replace($this->proxy->url);

        $options = $this->proxy->options;
        $options[RequestOptions::QUERY] = $request->getUri()->getQuery();
        $options[RequestOptions::HEADERS] ??= [];
        $options[RequestOptions::HEADERS] += $this->filterHeaders($request->getHeaders());
        $options[RequestOptions::HTTP_ERRORS] = false;

        $method = $request->getMethod();
        if (in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            if ($request->isFormData()) {
                $options[RequestOptions::FORM_PARAMS] = $request->parseForm();
            } else {
                $options[RequestOptions::BODY] = $request->body();
            }
        }

        try {
            $response = (new Client())->request($method, $url, $options);
        } catch (ConnectException $exception) {
            return new Response(
                status: HttpStatus::INTERNAL_SERVER_ERROR,
                body: $exception->getMessage()
            );
        }

        return new Response(
            $response->getStatusCode(),
            $response->getHeaders(),
            $response->getBody()->getContents()
        );
    }

    protected function filterHeaders(array $headers): array
    {
        unset(
            $headers['host'],
            $headers['content-length'],
        );
        return $headers;
    }
}