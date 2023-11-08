<?php declare(strict_types=1);

namespace lav45\MockServer\middlewares;

use Amp;
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

class WebhooksMiddleware extends BaseMiddleware
{
    /**
     * @param Webhook[] $webhooks
     */
    public function __construct(
        private readonly array     $webhooks,
        private readonly EnvParser $parser,
        private readonly Logger    $logger,
    )
    {
    }

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
    protected function internalHandler(array $webhooks): void
    {
        foreach ($webhooks as $webhook) {
            if ($delay = $webhook->delay) {
                $delay = (float)$this->parser->replace($delay);
                Amp\delay($delay);
            }
            try {
                $method = $this->parser->replace($webhook->method);
                $url = $this->parser->replace($webhook->url);
                $options = $this->parser->replace($webhook->options);
                (new Client())->request($method, $url, $options);
                $this->logger->info("Webhook: {$method} {$url}");
            } catch (RuntimeException $exception) {
                $this->logger->error($exception->getMessage());
            }
        }
    }
}