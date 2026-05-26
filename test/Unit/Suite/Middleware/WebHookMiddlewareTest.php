<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Middleware;

use Amp\Http\Client\Request as HttpClientRequest;
use Amp\Http\Client\Response as HttpClientResponse;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Lav45\MockServer\DataFactory\WebHooksFactory;
use Lav45\MockServer\Middleware\WebHookMiddleware;
use Lav45\MockServer\Parser\InlineParser;
use Lav45\MockServer\Parser\ParamParser;
use Lav45\MockServer\Parser\VariableParser;
use Lav45\MockServer\Responder\HttpClient;
use Lav45\MockServer\Responder\WebHookHandler;
use Lav45\MockServer\Test\Unit\Components\FakeHttpDriverClient;
use League\Uri\Http;
use PHPUnit\Framework\TestCase;
use Revolt\EventLoop;

final class WebHookMiddlewareTest extends TestCase
{
    private function createMiddleware(HttpClient $httpClient): WebHookMiddleware
    {
        return new WebHookMiddleware(new WebHooksFactory(), new WebHookHandler($httpClient));
    }

    private function createRequest(): Request
    {
        $request = new Request(new FakeHttpDriverClient(), 'POST', Http::new('https://localhost/'));
        $request->setAttribute('body', '');
        return $request;
    }

    private function createParser(array $data = []): VariableParser
    {
        $parser = new ParamParser(new class implements InlineParser {
            public function replace(mixed $data): mixed
            {
                return $data;
            }
        });
        return $data ? $parser->withData($data) : $parser;
    }

    private function createCapturingHttpClient(): HttpClient
    {
        return new class implements HttpClient {
            /** @var array<array{uri: string, method: string, body: string|null}> */
            public array $calls = [];

            public function request(
                string      $uri,
                string      $method = 'GET',
                array|null  $headers = null,
                string|null $body = null,
            ): HttpClientResponse {
                $this->calls[] = ['uri' => $uri, 'method' => $method, 'body' => $body];
                return new HttpClientResponse('1.1', 200, 'OK', [], '', new HttpClientRequest($uri));
            }
        };
    }

    private function nextReturning(int $status): \Closure
    {
        return static fn(Request $r): Response => new Response($status);
    }

    // --- Response passthrough ---

    public function testAlwaysCallsNext(): void
    {
        $httpClient = $this->createCapturingHttpClient();

        $request = $this->createRequest();
        $request->setAttribute('parser', $this->createParser());
        $request->setAttribute('data', []);

        $called = false;
        $next = function () use (&$called): Response {
            $called = true;
            return new Response(200);
        };

        $middleware = $this->createMiddleware($httpClient);
        $middleware($request, $next);

        $this->assertTrue($called);
    }

    public function testReturnsResponseFromNext(): void
    {
        $httpClient = $this->createCapturingHttpClient();

        $request = $this->createRequest();
        $request->setAttribute('parser', $this->createParser());
        $request->setAttribute('data', []);

        $middleware = $this->createMiddleware($httpClient);
        $response = $middleware($request, $this->nextReturning(204));

        $this->assertSame(204, $response->getStatus());
    }

    public function testReturnsResponseFromNextEvenWhenWebhooksExist(): void
    {
        $httpClient = $this->createCapturingHttpClient();

        $request = $this->createRequest();
        $request->setAttribute('parser', $this->createParser());
        $request->setAttribute('data', [
            'webhooks' => [['url' => 'https://hook.example.com']],
        ]);

        $middleware = $this->createMiddleware($httpClient);
        $response = $middleware($request, $this->nextReturning(201));

        $this->assertSame(201, $response->getStatus());
    }

    // --- Webhook sending ---

    public function testDoesNotSendWhenWebhooksKeyMissing(): void
    {
        $httpClient = $this->createCapturingHttpClient();

        $request = $this->createRequest();
        $request->setAttribute('parser', $this->createParser());
        $request->setAttribute('data', ['response' => ['text' => 'ok']]);

        $middleware = $this->createMiddleware($httpClient);
        $middleware($request, $this->nextReturning(200));

        $this->assertCount(0, $httpClient->calls);
    }

    public function testDoesNotSendWhenWebhooksArrayIsEmpty(): void
    {
        $httpClient = $this->createCapturingHttpClient();

        $request = $this->createRequest();
        $request->setAttribute('parser', $this->createParser());
        $request->setAttribute('data', ['webhooks' => []]);

        $middleware = $this->createMiddleware($httpClient);
        $middleware($request, $this->nextReturning(200));

        $this->assertCount(0, $httpClient->calls);
    }

    public function testSendsWebhookWhenDataContainsWebhooks(): void
    {
        $httpClient = $this->createCapturingHttpClient();

        $request = $this->createRequest();
        $request->setAttribute('parser', $this->createParser());
        $request->setAttribute('data', [
            'webhooks' => [['url' => 'https://hook.example.com']],
        ]);

        $middleware = $this->createMiddleware($httpClient);
        $middleware($request, $this->nextReturning(200));
        EventLoop::run();

        $this->assertCount(1, $httpClient->calls);
        $this->assertSame('https://hook.example.com', $httpClient->calls[0]['uri']);
    }

    public function testSendsMultipleWebhooks(): void
    {
        $httpClient = $this->createCapturingHttpClient();

        $request = $this->createRequest();
        $request->setAttribute('parser', $this->createParser());
        $request->setAttribute('data', [
            'webhooks' => [
                ['url' => 'https://hook1.example.com'],
                ['url' => 'https://hook2.example.com'],
            ],
        ]);

        $middleware = $this->createMiddleware($httpClient);
        $middleware($request, $this->nextReturning(200));
        EventLoop::run();

        $this->assertCount(2, $httpClient->calls);
        $this->assertSame('https://hook1.example.com', $httpClient->calls[0]['uri']);
        $this->assertSame('https://hook2.example.com', $httpClient->calls[1]['uri']);
    }

    public function testUsesParserFromRequestAttribute(): void
    {
        $httpClient = $this->createCapturingHttpClient();
        $parser = $this->createParser(['env' => ['hook_url' => 'https://hook.example.com']]);

        $request = $this->createRequest();
        $request->setAttribute('parser', $parser);
        $request->setAttribute('data', [
            'webhooks' => [['url' => '{env.hook_url}']],
        ]);

        $middleware = $this->createMiddleware($httpClient);
        $middleware($request, $this->nextReturning(200));
        EventLoop::run();

        $this->assertCount(1, $httpClient->calls);
        $this->assertSame('https://hook.example.com', $httpClient->calls[0]['uri']);
    }
}
