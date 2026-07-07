<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Extension\WebHook;

use Lav45\MockServer\Domain\ValueObject\Body;
use Lav45\MockServer\Domain\ValueObject\Delay;
use Lav45\MockServer\Domain\ValueObject\HttpHeaders;
use Lav45\MockServer\Domain\ValueObject\HttpMethod;
use Lav45\MockServer\Domain\ValueObject\Url;
use Lav45\MockServer\Domain\WebHook;
use Lav45\MockServer\Extension\WebHook\WebHookHandler;
use Lav45\MockServer\Test\Unit\Components\FakeHttpClient;
use Lav45\MockServer\Test\Unit\Components\FakeLogger;
use PHPUnit\Framework\TestCase;

final class WebHookHandlerTest extends TestCase
{
    private function createWebHook(
        string $url = 'https://hook.example.com',
        string $method = 'POST',
        array  $headers = [],
        string $body = '',
        float  $delay = 0.0,
    ): WebHook {
        return new WebHook(
            delay: new Delay($delay),
            url: new Url($url),
            method: new HttpMethod($method),
            headers: HttpHeaders::fromArray($headers),
            body: Body::new($body),
        );
    }

    public function testSendsWebhookWithCorrectUrl(): void
    {
        $httpClient = new FakeHttpClient();
        $handler = new WebHookHandler($httpClient);

        $handler->send($this->createWebHook(url: 'https://hook.example.com/notify'));

        $this->assertCount(1, $httpClient->calls);
        $this->assertSame('https://hook.example.com/notify', $httpClient->calls[0]['uri']);
    }

    public function testSendsWebhookWithCorrectMethod(): void
    {
        $httpClient = new FakeHttpClient();
        $handler = new WebHookHandler($httpClient);

        $handler->send($this->createWebHook(method: 'PUT'));

        $this->assertSame('PUT', $httpClient->calls[0]['method']);
    }

    public function testSendsWebhookWithCorrectBody(): void
    {
        $httpClient = new FakeHttpClient();
        $handler = new WebHookHandler($httpClient);

        $handler->send($this->createWebHook(body: '{"id":1}'));

        $this->assertSame('{"id":1}', $httpClient->calls[0]['body']->stream->read());
    }

    public function testSendsWebhookWithCorrectHeaders(): void
    {
        $httpClient = new FakeHttpClient();
        $handler = new WebHookHandler($httpClient);

        $handler->send($this->createWebHook(headers: ['X-Token' => 'secret']));

        $this->assertSame('secret', $httpClient->calls[0]['headers']['X-Token']);
    }

    public function testLogsErrorWhenHttpClientThrows(): void
    {
        $logger = new FakeLogger();
        $httpClient = new FakeHttpClient(exception: new \RuntimeException('connection refused'));
        $handler = new WebHookHandler($httpClient, $logger);

        $handler->send($this->createWebHook());

        $this->assertSame(['connection refused'], $logger->getMessages('error'));
    }
}
