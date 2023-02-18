<?php

namespace lav45\MockServer\middlewares;

use Amp;
use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use lav45\MockServer\EnvParser;
use lav45\MockServer\InvalidConfigException;
use lav45\MockServer\Mock\Webhook;
use Monolog\Logger;
use RuntimeException;

/**
 * Class WebhooksMiddleware
 * @package lav45\MockServer\middlewares
 */
class WebhooksMiddleware implements Middleware
{
    /**
     * @param Webhook[] $webhooks
     * @param Logger $logger
     * @param EnvParser $parser
     */
    public function __construct(
        private readonly array     $webhooks,
        private readonly Logger    $logger,
        private readonly EnvParser $parser,
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
     * @param Webhook[] $webhooks
     * @throws GuzzleException
     * @throws InvalidConfigException
     */
    protected function internalHandler(array $webhooks)
    {
        foreach ($webhooks as $webhook) {
            if ($webhook->delay) {
                Amp\delay($webhook->delay);
            }
            try {
                $options = $this->parser->replace($webhook->options);
                (new Client())->request($webhook->method, $webhook->url, $options);
                $this->logger->info("Webhook: {$webhook->method} {$webhook->url}");
            } catch (RuntimeException $exception) {
                $this->logger->error($exception->getMessage());
            }
        }
    }
}