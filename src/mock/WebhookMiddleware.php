<?php

namespace lav45\MockServer\mock;

use Amp;
use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use GuzzleHttp\Client;

/**
 * Class WebhookMiddleware
 * @package lav45\MockServer
 */
class WebhookMiddleware implements Middleware
{
    /**
     * @param WebhookMock $webhook
     */
    public function __construct(private readonly WebhookMock $webhook)
    {
    }

    /**
     * @param Request $request
     * @param RequestHandler $requestHandler
     * @return Response
     */
    public function handleRequest(Request $request, RequestHandler $requestHandler): Response
    {
        if ($this->webhook->url) {
            Amp\async(function () {
                if ($this->webhook->delay) {
                    Amp\delay($this->webhook->delay);
                }
                (new Client())->request(
                    $this->webhook->method,
                    $this->webhook->url,
                    $this->webhook->options
                );
            });
        }
        return $requestHandler->handleRequest($request);
    }
}