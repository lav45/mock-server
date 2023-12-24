<?php declare(strict_types=1);

namespace lav45\MockServer\Request\Middleware;

use Amp\Http\Client\BufferedContent;
use Amp\Http\Client\HttpContent;
use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use lav45\MockServer\EnvParser;
use lav45\MockServer\HttpClient;
use lav45\MockServer\Mock\Webhook;
use lav45\MockServer\Request\Wrapper\RequestWrapper;
use Monolog\Logger;
use RuntimeException;
use function Amp\async;
use function Amp\delay;

readonly class WebhooksMiddleware implements Middleware
{
    /**
     * @param Webhook[] $webhooks
     */
    public function __construct(
        private array      $webhooks,
        private Logger     $logger,
        private HttpClient $httpClient,
    )
    {
    }

    public function handleRequest(Request $request, RequestHandler $requestHandler): Response
    {
        $parser = $request->getAttribute(EnvParser::class);

        async(fn() => $this->internalHandler($this->webhooks, $parser));

        return $requestHandler->handleRequest($request);
    }

    /**
     * @param Webhook[] $webhooks
     */
    protected function internalHandler(array $webhooks, EnvParser $parser): void
    {
        foreach ($webhooks as $webhook) {
            if ($delay = $webhook->delay) {
                $delay = (float)$parser->replace($delay);
                delay($delay);
            }
            try {
                $url = $parser->replace($webhook->url);
                $query = $this->getQuery($url);
                $method = $parser->replace($webhook->method);
                $headers = $this->getHeaders($webhook, $parser);
                $body = $this->getBodyContent($webhook, $parser);

                $response = $this->httpClient->request(
                    uri: $url,
                    method: $method,
                    query: $query,
                    body: $body,
                    headers: $headers,
                );

                $statusCode = $response->getStatus();
                $message = "Webhook: {$statusCode} {$method} {$url}";
                ($statusCode === 200) ?
                    $this->logger->info($message) :
                    $this->logger->warning($message);
            } catch (RuntimeException $exception) {
                $this->logger->error($exception->getMessage());
            }
        }
    }

    private function getQuery(string $url): array
    {
        $query = parse_url($url, PHP_URL_QUERY);
        if (empty($query)) {
            return [];
        }
        return RequestWrapper::parseQuery($query);
    }

    private function getBodyContent(Webhook $webhook, EnvParser $parser): HttpContent|string
    {
        $text = $webhook->options['text'] ?? $webhook->text;
        if ($text) {
            return $text;
        }
        $json = $webhook->options['json'] ?? $webhook->json;
        return $json ? $this->parseContent($json, $parser) : '';
    }

    protected function getHeaders(Webhook $webhook, EnvParser $parser): array
    {
        $headers = $webhook->options['headers'] ?? $webhook->headers;
        return $parser->replace($headers);
    }

    private function parseContent(array $body, EnvParser $parser): HttpContent|string
    {
        $body = $parser->replace($body);
        $data = json_encode($body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return BufferedContent::fromString($data, 'application/json');
    }
}