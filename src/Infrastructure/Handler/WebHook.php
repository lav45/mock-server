<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Handler;

use Amp\Http\Client\Request as HttpRequest;
use Amp\Http\Client\Response as HttpResponse;
use Lav45\MockServer\Application\Query\Request\WebHook as WebHookInterface;
use Lav45\MockServer\Domain\Model\WebHook as WebHookItem;
use Lav45\MockServer\Infrastructure\Service\HttpClientInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

use function Amp\async;
use function Amp\delay;

final readonly class WebHook implements WebHookInterface
{
    private HttpClientInterface $httpClient;

    public function __construct(
        private LoggerInterface $logger,
        HttpClientInterface     $httpClient,
    )
    {
        $this->httpClient = $httpClient->withLogMessage(static function (HttpRequest $request, HttpResponse $response) {
            return "WebHook: {$response->getStatus()} {$request->getMethod()} {$request->getUri()}";
        });
    }

    public function send(WebHookItem ...$webHooks): void
    {
        async(function () use ($webHooks) {
            foreach ($webHooks as $webHook) {
                delay($webHook->delay->value);
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
        catch (RuntimeException $exception) {
            $this->logger->error($exception->getMessage());
        } // @codeCoverageIgnoreEnd
    }
}
