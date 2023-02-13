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
 * Class WebhooksMiddleware
 * @package lav45\MockServer\middlewares
 */
class WebhooksMiddleware implements Middleware
{
    /**
     * @param MockWebhook[] $mockWebhooks
     */
    public function __construct(private readonly array $mockWebhooks)
    {
    }

    /**
     * @param Request $request
     * @param RequestHandler $requestHandler
     * @return Response
     */
    public function handleRequest(Request $request, RequestHandler $requestHandler): Response
    {
        Amp\async(fn() => $this->internalHandler($this->mockWebhooks));
        return $requestHandler->handleRequest($request);
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function internalHandler(array $mockWebhooks)
    {
        foreach ($mockWebhooks as $mockWebhook) {
            if ($mockWebhook->delay) {
                Amp\delay($mockWebhook->delay);
            }
            (new Client())->request(
                $mockWebhook->method,
                $mockWebhook->url,
                $mockWebhook->options
            );
        }
    }
}