<?php

namespace lav45\MockServer\middlewares;

use Amp;
use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use GuzzleHttp\Client;
use lav45\MockServer\mock\MockWebhook;

/**
 * Class WebhookMiddleware
 * @package lav45\MockServer\middlewares
 */
class WebhookMiddleware implements Middleware
{
    /**
     * @param MockWebhook $mockWebhook
     */
    public function __construct(private readonly MockWebhook $mockWebhook)
    {
    }

    /**
     * @param Request $request
     * @param RequestHandler $requestHandler
     * @return Response
     */
    public function handleRequest(Request $request, RequestHandler $requestHandler): Response
    {
        if ($this->mockWebhook->url) {
            Amp\async(fn() => $this->internalHandler());
        }
        return $requestHandler->handleRequest($request);
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function internalHandler()
    {
        if ($this->mockWebhook->delay) {
            Amp\delay($this->mockWebhook->delay);
        }
        (new Client())->request(
            $this->mockWebhook->method,
            $this->mockWebhook->url,
            $this->mockWebhook->options
        );
    }
}