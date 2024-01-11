<?php declare(strict_types=1);

namespace lav45\MockServer\Request\Handler;

use Amp\Http\Client\BufferedContent;
use Amp\Http\Client\HttpContent;
use Amp\Http\Client\Request;
use Amp\Http\Client\Response;
use lav45\MockServer\EnvParser;
use lav45\MockServer\HttpClient;
use lav45\MockServer\Mock\Webhook;
use lav45\MockServer\Request\Wrapper\RequestWrapper;
use Psr\Log\LoggerInterface;
use RuntimeException;
use function Amp\async;
use function Amp\delay;

final readonly class WebhookHandler
{
    public function __construct(
        private LoggerInterface $logger,
        private EnvParser       $parser,
        private HttpClient      $httpClient,
    )
    {
    }

    public function send(array $webhooks): void
    {
        async(fn() => $this->internalSend($webhooks, clone $this->parser));
    }

    /**
     * @param Webhook[] $webhooks
     */
    protected function internalSend(array $webhooks, EnvParser $parser): void
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

                $this->httpClient
                    ->withLogMessage(static function (Request $request, Response $response) {
                        return "Webhook: {$response->getStatus()} {$request->getMethod()} {$request->getUri()}";
                    })
                    ->request(
                        uri: $url,
                        method: $method,
                        query: $query,
                        body: $body,
                        headers: $headers,
                    );
            }
            // @codeCoverageIgnoreStart
            catch (RuntimeException $exception) {
                $this->logger->error($exception->getMessage());
            }
            // @codeCoverageIgnoreEnd
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