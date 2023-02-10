<?php

namespace lav45\MockServer\mock;

use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\RequestOptions;

/**
 * Class MockResponseProxyMiddleware
 * @package lav45\MockServer\mock
 */
class MockResponseProxyMiddleware implements Middleware
{
    /**
     * @param ResponseMock $mockResponse
     */
    public function __construct(private readonly ResponseMock $mockResponse)
    {
    }

    /**
     * @param Request $request
     * @param RequestHandler $requestHandler
     * @return Response
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function handleRequest(Request $request, RequestHandler $requestHandler): Response
    {
        if (empty($this->mockResponse->proxyUrl)) {
            return $requestHandler->handleRequest($request);
        }

        $proxyUrl = RequestHelper::replaceAttributes($request, $this->mockResponse->proxyUrl);

        $options = $this->mockResponse->options;
        $options[RequestOptions::QUERY] = $request->getUri()->getQuery();

        try {
            $response = (new Client())->request($request->getMethod(), $proxyUrl, $options);
        } catch (ClientException $exception) {
            $response = $exception->getResponse();
        }

        return new Response(
            $response->getStatusCode(),
            $response->getHeaders(),
            $response->getBody()->getContents()
        );
    }
}