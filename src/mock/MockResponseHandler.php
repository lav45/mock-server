<?php

namespace lav45\MockServer\mock;

use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Server\Router;

/**
 * Class MockResponseHandler
 * @package lav45\MockServer
 */
class MockResponseHandler implements RequestHandler
{
    /**
     * @param ResponseMock $mockResponse
     */
    public function __construct(private readonly ResponseMock $mockResponse)
    {
    }

    /**
     * @param Request $request
     * @return Response
     * @throws \JsonException
     * @throws \Throwable
     */
    public function handleRequest(Request $request): Response
    {
        $response = new Response();
        $response->setStatus($this->mockResponse->status);
        $response->setHeaders($this->mockResponse->headers);

        $body = $this->renderBodyParams(
            $this->mockResponse->getBody(),
            $request->getAttribute(Router::class)
        );

        $response->setBody($body);

        return $response;
    }

    /**
     * @param string $body
     * @param array $args
     * @return string
     */
    protected function renderBodyParams(string $body, array $args)
    {
        foreach ($args as $key => $value) {
            $body = str_replace("{{$key}}", $value, $body);
        }
        return $body;
    }
}