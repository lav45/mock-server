<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Service;

use Amp\Http\Client\Request as HttpRequest;
use Amp\Http\Client\Response as HttpResponse;
use Lav45\MockServer\Application\Service\Webhook as WebhookService;
use Lav45\MockServer\Domain\ValueObject\Webhook as WebhookItem;
use Lav45\MockServer\Infrastructure\Wrapper\HttpClient;
use Psr\Log\LoggerInterface;
use RuntimeException;

use function Amp\async;
use function Amp\delay;

final readonly class Webhook implements WebhookService
{
    private HttpClient $httpClient;

    public function __construct(
        private LoggerInterface $logger,
        HttpClient              $httpClient,
    ) {
        $this->httpClient = $httpClient->withLogMessage(static function (HttpRequest $request, HttpResponse $response) {
            return "Webhook: {$response->getStatus()} {$request->getMethod()} {$request->getUri()}";
        });
    }

    public function send(WebhookItem ...$webhooks): void
    {
        async(function () use ($webhooks) {
            foreach ($webhooks as $webhook) {
                delay($webhook->delay->value);
                $this->request($webhook);
            }
        });
    }

    private function request(WebhookItem $webhook): void
    {
        try {
            $this->httpClient->request(
                uri: $webhook->url->value,
                method: $webhook->method->value,
                body: $webhook->body->toString(),
                headers: $webhook->headers->all(),
            );
        } // @codeCoverageIgnoreStart
        catch (RuntimeException $exception) {
            $this->logger->error($exception->getMessage());
        } // @codeCoverageIgnoreEnd
    }
}
