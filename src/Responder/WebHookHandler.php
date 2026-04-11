<?php declare(strict_types=1);

namespace Lav45\MockServer\Responder;

use Lav45\MockServer\Domain\Mock\WebHook as WebHookItem;
use Lav45\MockServer\Responder\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

use function Amp\async;
use function Amp\delay;

final readonly class WebHookHandler implements WebHookHandlerInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface     $logger = new NullLogger(),
    ) {}

    /**
     * @param list<WebHookItem> $webHooks
     */
    public function send(iterable $webHooks): void
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
            );
        } // @codeCoverageIgnoreStart
        catch (\RuntimeException $exception) {
            $this->logger->error($exception->getMessage());
        } // @codeCoverageIgnoreEnd
    }
}
