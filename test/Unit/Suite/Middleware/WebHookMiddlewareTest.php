<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Middleware;

use Lav45\MockServer\DataFactory\DataBuilder;
use Lav45\MockServer\DataFactory\WebHooksFactory;
use Lav45\MockServer\Driver\WebHookQueue;
use Lav45\MockServer\Engine\Http\ClientResponse;
use Lav45\MockServer\Engine\Http\ServerRequest;
use Lav45\MockServer\Engine\Http\ServerResponse;
use Lav45\MockServer\Engine\HttpClient;
use Lav45\MockServer\Middleware\MiddlewareHandler;
use Lav45\MockServer\Middleware\WebHookMiddleware;
use Lav45\MockServer\Responder\WebHookHandler;
use Lav45\MockServer\Test\Unit\Components\CallableHandler;
use Lav45\MockServer\Test\Unit\Components\FakeServerRequest;
use PHPUnit\Framework\TestCase;
use Revolt\EventLoop;

final class WebHookMiddlewareTest extends TestCase
{
    private function createMiddleware(HttpClient $httpClient): WebHookMiddleware
    {
        return new WebHookMiddleware(
            new WebHooksFactory(new DataBuilder()),
            new WebHookQueue(new WebHookHandler($httpClient)),
        );
    }

    private function createRequest(): ServerRequest
    {
        return new FakeServerRequest('POST', 'https://localhost/');
    }

    private function createCapturingHttpClient(): HttpClient
    {
        return new class implements HttpClient {
            public function withLabel(string $label): self
            {
                return $this;
            }

            /** @var array<array{uri: string, method: string, body: string|null}> */
            public array $calls = [];

            public function request(
                string      $uri,
                string      $method = 'GET',
                array|null  $headers = null,
                string|null $body = null,
            ): ClientResponse {
                $this->calls[] = ['uri' => $uri, 'method' => $method, 'body' => $body];
                return new ClientResponse(200, [], '');
            }
        };
    }

    private function nextReturning(int $status): MiddlewareHandler
    {
        return new CallableHandler(static fn(ServerRequest $r): ServerResponse => new ServerResponse($status));
    }

    // --- Response passthrough ---

    public function testAlwaysCallsNext(): void
    {
        $httpClient = $this->createCapturingHttpClient();

        $request = $this->createRequest();
        $request->setAttribute('data', []);

        $called = false;
        $next = function () use (&$called): ServerResponse {
            $called = true;
            return new ServerResponse(200);
        };

        $middleware = $this->createMiddleware($httpClient);
        $middleware->process($request, new CallableHandler($next));

        $this->assertTrue($called);
    }

    public function testReturnsResponseFromNext(): void
    {
        $httpClient = $this->createCapturingHttpClient();

        $request = $this->createRequest();
        $request->setAttribute('data', []);

        $middleware = $this->createMiddleware($httpClient);
        $response = $middleware->process($request, $this->nextReturning(204));

        $this->assertSame(204, $response->getStatus());
    }

    public function testReturnsResponseFromNextEvenWhenWebhooksExist(): void
    {
        $httpClient = $this->createCapturingHttpClient();

        $request = $this->createRequest();
        $request->setAttribute('data', [
            'webhooks' => [['url' => 'https://hook.example.com']],
        ]);

        $middleware = $this->createMiddleware($httpClient);
        $response = $middleware->process($request, $this->nextReturning(201));

        $this->assertSame(201, $response->getStatus());
    }

    // --- Webhook sending ---

    public function testDoesNotSendWhenWebhooksKeyMissing(): void
    {
        $httpClient = $this->createCapturingHttpClient();

        $request = $this->createRequest();
        $request->setAttribute('data', ['response' => ['text' => 'ok']]);

        $middleware = $this->createMiddleware($httpClient);
        $middleware->process($request, $this->nextReturning(200));

        $this->assertCount(0, $httpClient->calls);
    }

    public function testDoesNotSendWhenWebhooksArrayIsEmpty(): void
    {
        $httpClient = $this->createCapturingHttpClient();

        $request = $this->createRequest();
        $request->setAttribute('data', ['webhooks' => []]);

        $middleware = $this->createMiddleware($httpClient);
        $middleware->process($request, $this->nextReturning(200));

        $this->assertCount(0, $httpClient->calls);
    }

    public function testSendsWebhookWhenDataContainsWebhooks(): void
    {
        $httpClient = $this->createCapturingHttpClient();

        $request = $this->createRequest();
        $request->setAttribute('data', [
            'webhooks' => [['url' => 'https://hook.example.com']],
        ]);

        $middleware = $this->createMiddleware($httpClient);
        $middleware->process($request, $this->nextReturning(200));
        EventLoop::run();

        $this->assertCount(1, $httpClient->calls);
        $this->assertSame('https://hook.example.com', $httpClient->calls[0]['uri']);
    }

    public function testSendsMultipleWebhooks(): void
    {
        $httpClient = $this->createCapturingHttpClient();

        $request = $this->createRequest();
        $request->setAttribute('data', [
            'webhooks' => [
                ['url' => 'https://hook1.example.com'],
                ['url' => 'https://hook2.example.com'],
            ],
        ]);

        $middleware = $this->createMiddleware($httpClient);
        $middleware->process($request, $this->nextReturning(200));
        EventLoop::run();

        $this->assertCount(2, $httpClient->calls);
        $this->assertSame('https://hook1.example.com', $httpClient->calls[0]['uri']);
        $this->assertSame('https://hook2.example.com', $httpClient->calls[1]['uri']);
    }
}
