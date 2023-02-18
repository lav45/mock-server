<?php

namespace lav45\MockServer\RequestHandler;

use Amp\ByteStream\BufferException;
use Amp\ByteStream\StreamException;
use Amp\Http\HttpStatus;
use Amp\Http\Server\ClientException;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use lav45\MockServer\RequestHelper;
use lav45\MockServer\EnvParser;
use lav45\MockServer\Mock\Response\Proxy;

/**
 * Class ProxyHandler
 * @package lav45\MockServer\middlewares
 */
class ProxyHandler implements RequestHandler
{
    /**
     * @param Proxy $proxy
     * @param EnvParser $parser
     */
    public function __construct(
        private readonly Proxy     $proxy,
        private readonly EnvParser $parser
    )
    {
    }

    /**
     * @param Request $request
     * @return Response
     * @throws BufferException
     * @throws StreamException
     * @throws GuzzleException
     * @throws ClientException
     */
    public function handleRequest(Request $request): Response
    {
        $url = $this->parser->replaceAttribute($this->proxy->url);

        $options = $this->proxy->options;
        $options[RequestOptions::QUERY] = $request->getUri()->getQuery();
        $options[RequestOptions::HEADERS] ??= [];
        $options[RequestOptions::HEADERS] += $this->filterHeaders($request->getHeaders());
        $options[RequestOptions::HTTP_ERRORS] = false;

        $method = $request->getMethod();
        $helper = new RequestHelper($request);
        if ($method === 'POST') {
            if ($helper->isFormData()) {
                $options[RequestOptions::FORM_PARAMS] = $helper->post();
            } else {
                $options[RequestOptions::BODY] = $helper->body();
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

    /**
     * @param array $headers
     * @return array
     */
    protected function filterHeaders($headers)
    {
        unset(
            $headers['host'],
            $headers['content-length'],
        );
        return $headers;
    }
}