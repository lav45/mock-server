<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Extension\WebHook;

use Lav45\MockServer\Domain\ValueObject\Body;
use Lav45\MockServer\Domain\ValueObject\Delay;
use Lav45\MockServer\Domain\ValueObject\HttpHeaders;
use Lav45\MockServer\Domain\ValueObject\HttpMethod;
use Lav45\MockServer\Domain\ValueObject\Url;
use Lav45\MockServer\Domain\WebHook;
use Lav45\MockServer\Engine\Http\ClientResponse;
use Lav45\MockServer\Engine\HttpClient;
use Lav45\MockServer\Extension\WebHook\WebHookHandler;
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

    private function createThrowingHttpClient(): HttpClient
    {
        return new class implements HttpClient {
            public function withLabel(string $label): self
            {
                return $this;
            }

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
            public function withLabel(string $label): self
            {
                return $this;
            }

            public array $calls = [];

            public function request(
                string      $uri,
                string      $method = 'GET',
                array|null  $headers = null,
                string|null $body = null,
            ): ClientResponse {
                $this->calls[] = [
                    'uri' => $uri,
                    'method' => $method,
                    'headers' => $headers,
                    'body' => $body,
                ];
                return new ClientResponse(200, [], '');
            }
        };
    }

    public function testSendsWebhookWithCorrectUrl(): void
    {
        $httpClient = $this->createCapturingHttpClient();
        $handler = new WebHookHandler($httpClient);

        $handler->send($this->createWebHook(url: 'https://hook.example.com/notify'));

        $this->assertCount(1, $httpClient->calls);
        $this->assertSame('https://hook.example.com/notify', $httpClient->calls[0]['uri']);
    }

    public function testSendsWebhookWithCorrectMethod(): void
    {
        $httpClient = $this->createCapturingHttpClient();
        $handler = new WebHookHandler($httpClient);

        $handler->send($this->createWebHook(method: 'PUT'));

        $this->assertSame('PUT', $httpClient->calls[0]['method']);
    }

    public function testSendsWebhookWithCorrectBody(): void
    {
        $httpClient = $this->createCapturingHttpClient();
        $handler = new WebHookHandler($httpClient);

        $handler->send($this->createWebHook(body: '{"id":1}'));

        $this->assertSame('{"id":1}', $httpClient->calls[0]['body']);
    }

    public function testSendsWebhookWithCorrectHeaders(): void
    {
        $httpClient = $this->createCapturingHttpClient();
        $handler = new WebHookHandler($httpClient);

        $handler->send($this->createWebHook(headers: ['X-Token' => 'secret']));

        $this->assertSame('secret', $httpClient->calls[0]['headers']['X-Token']);
    }

    public function testLogsErrorWhenHttpClientThrows(): void
    {
        $logger = new FakeLogger();
        $handler = new WebHookHandler($this->createThrowingHttpClient(), $logger);

        $handler->send($this->createWebHook());

        $this->assertSame(['connection refused'], $logger->getMessages('error'));
    }
}
