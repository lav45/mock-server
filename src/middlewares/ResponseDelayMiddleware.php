<?php

namespace lav45\MockServer\middlewares;

use Amp;
use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;

/**
 * Class ResponseDelayMiddleware
 * @package lav45\MockServer\middlewares
 */
class ResponseDelayMiddleware implements Middleware
{
    /**
     * @param float $delay
     */
    public function __construct(private readonly float $delay)
    {
    }

    /**
     * @param Request $request
     * @param RequestHandler $requestHandler
     * @return Response
     */
    public function handleRequest(Request $request, RequestHandler $requestHandler): Response
    {
        if ($this->delay) {
            Amp\delay($this->delay);
        }
        return $requestHandler->handleRequest($request);
    }
}