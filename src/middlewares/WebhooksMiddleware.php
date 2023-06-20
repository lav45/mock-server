<?php

namespace lav45\MockServer\middlewares;

use Amp;
use Amp\ByteStream\BufferException;
use Amp\ByteStream\StreamException;
use Amp\Http\Server\ClientException;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use lav45\MockServer\EnvParser;
use lav45\MockServer\InvalidConfigException;
use lav45\MockServer\Mock\Webhook;
use lav45\MockServer\Request\RequestWrapper;
use lav45\MockServer\RequestHandler\WrappedRequestHandlerInterface;
use Monolog\Logger;
use RuntimeException;

/**
 * Class WebhooksMiddleware
 * @package lav45\MockServer\middlewares
 */
class WebhooksMiddleware extends BaseMiddleware
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
     * @param RequestWrapper $request
     * @param RequestHandler $requestHandler
     * @return Response
     * @throws BufferException
     * @throws StreamException
     * @throws ClientException
     */
    public function handleWrappedRequest(RequestWrapper $request, RequestHandler $requestHandler): Response
    {
        Amp\async(fn() => $this->internalHandler($this->webhooks));
        if ($requestHandler instanceof WrappedRequestHandlerInterface) {
            return $requestHandler->handleWrappedRequest($request);
        }
        return $requestHandler->handleRequest($request->getRequest());
    }

    /**
     * @param Webhook[] $webhooks
     * @throws GuzzleException
     * @throws InvalidConfigException
     */
    protected function internalHandler(array $webhooks)
    {
        foreach ($webhooks as $webhook) {
            if (is_string($webhook->delay)) {
                $delay = $this->parser->replaceAttribute($webhook->delay);
            } else {
                $delay = $webhook->delay;
            }
            if ($delay) {
                Amp\delay($delay);
            }
            try {
                $method = $this->parser->replaceAttribute($webhook->method);
                $url = $this->parser->replaceAttribute($webhook->url);
                $options = $this->parser->replace($webhook->options);
                (new Client())->request($method, $url, $options);
                $this->logger->info("Webhook: {$method} {$url}");
            } catch (RuntimeException $exception) {
                $this->logger->error($exception->getMessage());
            }
        }
    }
}