<?php

namespace lav45\MockServer\mock;

use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;

/**
 * Class ResponseHandler
 * @package lav45\MockServer
 */
class ResponseHandler implements RequestHandler
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

        $body = RequestHelper::replaceAttributes($request, $this->mockResponse->getBody());

        $response->setBody($body);

        return $response;
    }
}