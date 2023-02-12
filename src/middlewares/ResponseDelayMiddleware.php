<?php

namespace lav45\MockServer\middlewares;

use Amp;
use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use lav45\MockServer\mock\MockResponse;

/**
 * Class ResponseDelayMiddleware
 * @package lav45\MockServer\middlewares
 */
class ResponseDelayMiddleware implements Middleware
{
    /**
     * @param MockResponse $mockResponse
     */
    public function __construct(private readonly MockResponse $mockResponse)
    {
    }

    /**
     * @param Request $request
     * @param RequestHandler $requestHandler
     * @return Response
     */
    public function handleRequest(Request $request, RequestHandler $requestHandler): Response
    {
        if ($this->mockResponse->delay) {
            Amp\delay($this->mockResponse->delay);
        }
        return $requestHandler->handleRequest($request);
    }
}