<?php declare(strict_types=1);

namespace lav45\MockServer\middlewares;

use Amp;
use Amp\Http\Client\BufferedContent;
use Amp\Http\Client\HttpContent;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use lav45\MockServer\EnvParser;
use lav45\MockServer\HttpClient;
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
        private readonly array      $webhooks,
        private readonly EnvParser  $parser,
        private readonly Logger     $logger,
        private readonly HttpClient $httpClient,
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
     */
    protected function internalHandler(array $webhooks): void
    {
        foreach ($webhooks as $webhook) {
            if ($delay = $webhook->delay) {
                $delay = (float)$this->parser->replace($delay);
                Amp\delay($delay);
            }
            try {
                $url = $this->parser->replace($webhook->url);
                $method = $this->parser->replace($webhook->method);
                $headers = $this->getHeaders($webhook);
                $body = $this->getBodyContent($webhook);

                $response = $this->httpClient->request(
                    url: $url,
                    method: $method,
                    body: $body,
                    headers: $headers,
                );

                $statusCode = $response->getStatus();
                $message = "Webhook: {$method} {$url} => code: {$statusCode}";
                ($statusCode === 200) ?
                    $this->logger->info($message) :
                    $this->logger->warning($message);
            } catch (RuntimeException $exception) {
                $this->logger->error($exception->getMessage());
            }
        }
    }

    private function getBodyContent(Webhook $webhook): HttpContent|string
    {
        $text = $webhook->options['text'] ?? $webhook->text;
        if ($text) {
            return $text;
        }
        $json = $webhook->options['json'] ?? $webhook->json;
        return $json ? $this->parseContent($json) : '';
    }

    protected function getHeaders(Webhook $webhook): array
    {
        $headers = $webhook->options['headers'] ?? $webhook->headers;
        return $this->parser->replace($headers);
    }

    private function parseContent(array $body): HttpContent|string
    {
        $body = $this->parser->replace($body);
        $data = json_encode($body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return BufferedContent::fromString($data, 'application/json');
    }
}