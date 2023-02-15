<?php

namespace lav45\MockServer\middlewares;

use Amp;
use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\TransferException;
use lav45\MockServer\mock\MockWebhook;
use Monolog\Logger;

/**
 * Class WebhooksMiddleware
 * @package lav45\MockServer\middlewares
 */
class WebhooksMiddleware implements Middleware
{
    /**
     * @param MockWebhook[] $webhooks
     */
    public function __construct(
        private readonly array  $webhooks,
        private readonly Logger $logger
    )
    {
    }

    /**
     * @param Request $request
     * @param RequestHandler $requestHandler
     * @return Response
     */
    public function handleRequest(Request $request, RequestHandler $requestHandler): Response
    {
        Amp\async(fn() => $this->internalHandler($this->webhooks));
        return $requestHandler->handleRequest($request);
    }

    /**
     * @param array $webhooks
     * @throws GuzzleException
     */
    protected function internalHandler(array $webhooks)
    {
        foreach ($webhooks as $webhook) {
            if ($webhook->delay) {
                Amp\delay($webhook->delay);
            }
            try {
                (new Client())->request($webhook->method, $webhook->url, $webhook->options);
                $this->logger->info("Webhook: {$webhook->method} {$webhook->url}");
            } catch (TransferException $exception) {
                $this->logger->error($exception->getMessage());
            }
        }
    }
}