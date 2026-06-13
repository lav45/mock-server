<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Responder;

use Amp\Http\Client\Request as HttpClientRequest;
use Amp\Http\Client\Response as HttpClientResponse;
use Lav45\MockServer\Domain\ValueObject\Body;
use Lav45\MockServer\Domain\ValueObject\Delay;
use Lav45\MockServer\Domain\ValueObject\HttpHeaders;
use Lav45\MockServer\Domain\ValueObject\HttpMethod;
use Lav45\MockServer\Domain\ValueObject\Url;
use Lav45\MockServer\Domain\WebHook;
use Lav45\MockServer\Domain\WebHooks;
use Lav45\MockServer\Responder\HttpClient;
use Lav45\MockServer\Responder\WebHookHandler;
use Lav45\MockServer\Test\Unit\Components\FakeLogger;
use PHPUnit\Framework\TestCase;
use Revolt\EventLoop;

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

    private function createThrowingHttpClient(): HttpClient
    {
        return new class implements HttpClient {
            public function request(
                string      $uri,
                string      $method = 'GET',
                array|null  $headers = null,
                string|null $body = null,
            ): never {
                throw new \RuntimeException('connection refused');
            }
        };
    }

    private function createCapturingHttpClient(): HttpClient
    {
        return new class implements HttpClient {
            public array $calls = [];

            public function request(
                string      $uri,
                string      $method = 'GET',
                array|null  $headers = null,
                string|null $body = null,
            ): HttpClientResponse {
                $this->calls[] = [
                    'uri' => $uri,
                    'method' => $method,
                    'headers' => $headers,
                    'body' => $body,
                ];
                return new HttpClientResponse('1.1', 200, 'OK', [], '', new HttpClientRequest($uri));
            }
        };
    }

    public function testSendsWebhookWithCorrectUrl(): void
    {
        $httpClient = $this->createCapturingHttpClient();
        $handler = new WebHookHandler($httpClient);

        $handler->send(new WebHooks($this->createWebHook(url: 'https://hook.example.com/notify')));
        EventLoop::run();

        $this->assertCount(1, $httpClient->calls);
        $this->assertSame('https://hook.example.com/notify', $httpClient->calls[0]['uri']);
    }

    public function testSendsWebhookWithCorrectMethod(): void
    {
        $httpClient = $this->createCapturingHttpClient();
        $handler = new WebHookHandler($httpClient);

        $handler->send(new WebHooks($this->createWebHook(method: 'PUT')));
        EventLoop::run();

        $this->assertSame('PUT', $httpClient->calls[0]['method']);
    }

    public function testSendsWebhookWithCorrectBody(): void
    {
        $httpClient = $this->createCapturingHttpClient();
        $handler = new WebHookHandler($httpClient);

        $handler->send(new WebHooks($this->createWebHook(body: '{"id":1}')));
        EventLoop::run();

        $this->assertSame('{"id":1}', $httpClient->calls[0]['body']);
    }

    public function testSendsWebhookWithCorrectHeaders(): void
    {
        $httpClient = $this->createCapturingHttpClient();
        $handler = new WebHookHandler($httpClient);

        $handler->send(new WebHooks($this->createWebHook(headers: ['X-Token' => 'secret'])));
        EventLoop::run();

        $this->assertSame('secret', $httpClient->calls[0]['headers']['X-Token']);
    }

    public function testSendsMultipleWebhooksInOrder(): void
    {
        $httpClient = $this->createCapturingHttpClient();
        $handler = new WebHookHandler($httpClient);

        $handler->send(new WebHooks(
            $this->createWebHook(url: 'https://hook1.example.com'),
            $this->createWebHook(url: 'https://hook2.example.com'),
            $this->createWebHook(url: 'https://hook3.example.com'),
        ));
        EventLoop::run();

        $this->assertCount(3, $httpClient->calls);
        $this->assertSame('https://hook1.example.com', $httpClient->calls[0]['uri']);
        $this->assertSame('https://hook2.example.com', $httpClient->calls[1]['uri']);
        $this->assertSame('https://hook3.example.com', $httpClient->calls[2]['uri']);
    }

    public function testDoesNotSendWhenWebHooksIsEmpty(): void
    {
        $httpClient = $this->createCapturingHttpClient();
        $handler = new WebHookHandler($httpClient);

        $handler->send(new WebHooks());
        EventLoop::run();

        $this->assertCount(0, $httpClient->calls);
    }

    public function testSendsWebhookAfterDelay(): void
    {
        $httpClient = $this->createCapturingHttpClient();
        $handler = new WebHookHandler($httpClient);

        $handler->send(new WebHooks($this->createWebHook(delay: 0.001)));
        EventLoop::run();

        $this->assertCount(1, $httpClient->calls);
    }

    public function testLogsErrorWhenHttpClientThrows(): void
    {
        $logger = new FakeLogger();
        $handler = new WebHookHandler($this->createThrowingHttpClient(), $logger);

        $handler->send(new WebHooks($this->createWebHook()));
        EventLoop::run();

        $this->assertSame(['connection refused'], $logger->getMessages('error'));
    }

    public function testContinuesSendingAfterException(): void
    {
        $logger = new FakeLogger();
        $handler = new WebHookHandler($this->createThrowingHttpClient(), $logger);

        $handler->send(new WebHooks(
            $this->createWebHook(url: 'https://hook1.example.com'),
            $this->createWebHook(url: 'https://hook2.example.com'),
        ));
        EventLoop::run();

        $this->assertCount(2, $logger->getMessages('error'));
    }
}
