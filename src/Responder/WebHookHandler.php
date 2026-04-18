<?php declare(strict_types=1);

namespace Lav45\MockServer\Responder;

use Lav45\MockServer\Domain\WebHooks;
use Lav45\MockServer\Domain\WebHooks\WebHook;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

use function Amp\async;
use function Amp\delay;

final readonly class WebHookHandler
{
    public function __construct(
        private HttpClient      $httpClient,
        private LoggerInterface $logger = new NullLogger(),
    ) {}

    public function send(WebHooks $webHooks): void
    {
        async(function () use ($webHooks) {
            foreach ($webHooks->items as $webHook) {
                if ($webHook->delay->value > 0) {
                    delay($webHook->delay->value);
                }
                $this->request($webHook);
            }
        });
    }

    private function request(WebHook $webHook): void
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
