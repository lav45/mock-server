<?php declare(strict_types=1);

namespace Lav45\MockServer\Responder;

use Lav45\MockServer\Domain\WebHook;
use Lav45\MockServer\Engine\HttpClient;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final readonly class WebHookHandler implements \Lav45\MockServer\Engine\WebHookHandler
{
    public function __construct(
        private HttpClient      $httpClient,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    public function send(WebHook $webHook): void
    {
        try {
            $this->httpClient->request(
                uri: $webHook->url->value,
                method: $webHook->method->value,
                headers: $webHook->headers->toArray(),
                body: $webHook->body->toString(),
            );
        } catch (\RuntimeException $exception) {
            $this->logger->error($exception->getMessage());
        }
    }
}
