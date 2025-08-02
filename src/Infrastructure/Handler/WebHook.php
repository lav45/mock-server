<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Handler;

use Lav45\MockServer\Application\Query\Request\WebHook as WebHookInterface;
use Lav45\MockServer\Domain\Model\WebHook as WebHookItem;
use Lav45\MockServer\Infrastructure\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

use function Amp\async;
use function Amp\delay;

final readonly class WebHook implements WebHookInterface
{
    public function __construct(
        private LoggerInterface     $logger,
        private HttpClientInterface $httpClient,
    ) {}

    public function send(WebHookItem ...$webHooks): void
    {
        async(function () use ($webHooks) {
            foreach ($webHooks as $webHook) {
                if ($webHook->delay->value > 0) {
                    delay($webHook->delay->value);
                }
                $this->request($webHook);
            }
        });
    }

    private function request(WebHookItem $webHook): void
    {
        try {
            $this->httpClient->request(
                uri: $webHook->url->value,
                method: $webHook->method->value,
                body: $webHook->body->toString(),
                headers: $webHook->headers->toArray(),
                logLabel: 'WebHook',
            );
        } // @codeCoverageIgnoreStart
        catch (\RuntimeException $exception) {
            $this->logger->error($exception->getMessage());
        } // @codeCoverageIgnoreEnd
    }
}
