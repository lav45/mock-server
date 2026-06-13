<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Middleware;

use Amp\Http\Client\Request as HttpClientRequest;
use Amp\Http\Client\Response as HttpClientResponse;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Lav45\MockServer\DataFactory\DataBuilder;
use Lav45\MockServer\DataFactory\DirectFactory;
use Lav45\MockServer\Middleware\DirectMiddleware;
use Lav45\MockServer\Middleware\MiddlewareHandler;
use Lav45\MockServer\Parser\InlineParser;
use Lav45\MockServer\Parser\ParamParser;
use Lav45\MockServer\Parser\VariableParser;
use Lav45\MockServer\Responder\DirectHandler;
use Lav45\MockServer\Responder\HttpClient;
use Lav45\MockServer\Test\Unit\Components\CallableHandler;
use Lav45\MockServer\Test\Unit\Components\FakeHttpDriverClient;
use Lav45\MockServer\Test\Unit\Components\FakeLogger;
use League\Uri\Http;
use PHPUnit\Framework\TestCase;

final class DirectMiddlewareTest extends TestCase
{
    private function createMiddleware(HttpClient $httpClient, FakeLogger $logger = new FakeLogger()): DirectMiddleware
    {
        return new DirectMiddleware(
            new DirectFactory(new DataBuilder()),
            new DirectHandler($httpClient, $logger),
        );
    }

    private function createRequest(string $method = 'GET'): Request
    {
        $request = new Request(new FakeHttpDriverClient(), $method, Http::new('https://localhost/api/123'));
        $request->setAttribute('body', '');
        return $request;
    }

    private function createParser(): VariableParser
    {
        return new ParamParser(new class implements InlineParser {
            public function replace(mixed $data): mixed
            {
                return $data;
            }
        });
    }

    private function createHttpClientStub(array $responseBody): HttpClient
    {
        return new readonly class ($responseBody, 200) implements HttpClient {
            public function __construct(
                private array $responseBody,
                private int   $status,
            ) {}

            public function request(
                string      $uri,
                string      $method = 'GET',
                array|null  $headers = null,
                string|null $body = null,
            ): HttpClientResponse {
                return new HttpClientResponse(
                    '1.1',
                    $this->status,
                    'OK',
                    [],
                    \json_encode($this->responseBody, JSON_THROW_ON_ERROR),
                    new HttpClientRequest($uri),
                );
            }
        };
    }

    private function nextCapturing(array &$capturedData): MiddlewareHandler
    {
        return new CallableHandler(static function (Request $request) use (&$capturedData): Response {
            $capturedData = $request->getAttribute('data');
            return new Response(200);
        });
    }

    // --- Passthrough ---

    public function testPassesThroughToNextWhenDirectKeyMissing(): void
    {
        $request = $this->createRequest();
        $request->setAttribute('parser', $this->createParser());
        $request->setAttribute('data', ['response' => ['text' => 'original']]);

        $called = false;
        $next = function (Request $r) use (&$called): Response {
            $called = true;
            return new Response(418);
        };

        $middleware = $this->createMiddleware($this->createHttpClientStub([]));
        $response = $middleware->process($request, new CallableHandler($next));

        $this->assertTrue($called);
        $this->assertSame(418, $response->getStatus());
    }

    // --- Always continues pipeline ---

    public function testAlwaysCallsNextAfterProcessing(): void
    {
        $request = $this->createRequest();
        $request->setAttribute('parser', $this->createParser());
        $request->setAttribute('data', ['direct' => ['url' => 'https://remote.example.com']]);

        $called = false;
        $next = function (Request $r) use (&$called): Response {
            $called = true;
            return new Response(200);
        };

        $middleware = $this->createMiddleware($this->createHttpClientStub([]));
        $middleware->process($request, new CallableHandler($next));

        $this->assertTrue($called);
    }

    // --- Response merging ---

    public function testSetsResponseFromRemoteData(): void
    {
        $request = $this->createRequest();
        $request->setAttribute('parser', $this->createParser());
        $request->setAttribute('data', ['direct' => ['url' => 'https://remote.example.com']]);

        $capturedData = [];
        $httpClient = $this->createHttpClientStub(['response' => ['status' => 201, 'json' => ['id' => 42]]]);
        $next = $this->nextCapturing($capturedData);
        $this->createMiddleware($httpClient)->process($request, $next);

        $this->assertSame(['status' => 201, 'json' => ['id' => 42]], $capturedData['response']);
    }

    public function testOverwritesExistingResponseWithRemoteData(): void
    {
        $request = $this->createRequest();
        $request->setAttribute('parser', $this->createParser());
        $request->setAttribute('data', [
            'direct' => ['url' => 'https://remote.example.com'],
            'response' => ['text' => 'original'],
        ]);

        $capturedData = [];
        $httpClient = $this->createHttpClientStub(['response' => ['text' => 'from remote']]);
        $next = $this->nextCapturing($capturedData);
        $this->createMiddleware($httpClient)->process($request, $next);

        $this->assertSame(['text' => 'from remote'], $capturedData['response']);
    }

    public function testLogsWarningWhenOverwritingExistingResponse(): void
    {
        $logger = new FakeLogger();
        $request = $this->createRequest('POST');
        $request->setAttribute('parser', $this->createParser());
        $request->setAttribute('data', [
            'request' => ['method' => 'POST', 'path' => '/api/payment'],
            'direct' => ['url' => 'https://remote.example.com'],
            'response' => ['text' => 'original'],
        ]);

        $httpClient = $this->createHttpClientStub(['response' => ['text' => 'from remote']]);
        $middleware = $this->createMiddleware($httpClient, $logger);
        $middleware->process($request, new CallableHandler(fn() => new Response(200)));

        $warnings = $logger->getMessages('warning');
        $this->assertCount(1, $warnings);
        $this->assertStringContainsString('/api/payment', $warnings[0]);
    }

    public function testDoesNotLogWarningWhenNoExistingResponse(): void
    {
        $logger = new FakeLogger();
        $request = $this->createRequest();
        $request->setAttribute('parser', $this->createParser());
        $request->setAttribute('data', ['direct' => ['url' => 'https://remote.example.com']]);

        $httpClient = $this->createHttpClientStub(['response' => ['text' => 'from remote']]);
        $middleware = $this->createMiddleware($httpClient, $logger);
        $middleware->process($request, new CallableHandler(fn() => new Response(200)));

        $this->assertCount(0, $logger->getMessages('warning'));
    }

    public function testDataUnchangedWhenRemoteReturnsNoResponseOrWebhooks(): void
    {
        $request = $this->createRequest();
        $request->setAttribute('parser', $this->createParser());
        $request->setAttribute('data', [
            'direct' => ['url' => 'https://remote.example.com'],
            'response' => ['text' => 'kept'],
        ]);

        $capturedData = [];
        $httpClient = $this->createHttpClientStub([]);
        $next = $this->nextCapturing($capturedData);
        $this->createMiddleware($httpClient)->process($request, $next);

        $this->assertSame(['text' => 'kept'], $capturedData['response']);
        $this->assertArrayNotHasKey('webhooks', $capturedData);
    }

    // --- Webhook merging ---

    public function testSetsWebhooksFromRemoteDataWhenNoneExist(): void
    {
        $remoteHooks = [['url' => 'https://hook.example.com']];

        $request = $this->createRequest();
        $request->setAttribute('parser', $this->createParser());
        $request->setAttribute('data', ['direct' => ['url' => 'https://remote.example.com']]);

        $capturedData = [];
        $httpClient = $this->createHttpClientStub(['webhooks' => $remoteHooks]);
        $next = $this->nextCapturing($capturedData);
        $this->createMiddleware($httpClient)->process($request, $next);

        $this->assertSame($remoteHooks, $capturedData['webhooks']);
    }

    public function testMergesRemoteWebhooksWithExistingOnes(): void
    {
        $existingHook = ['url' => 'https://existing.example.com'];
        $remoteHook = ['url' => 'https://remote-hook.example.com'];

        $request = $this->createRequest();
        $request->setAttribute('parser', $this->createParser());
        $request->setAttribute('data', [
            'direct' => ['url' => 'https://remote.example.com'],
            'webhooks' => [$existingHook],
        ]);

        $capturedData = [];
        $httpClient = $this->createHttpClientStub(['webhooks' => [$remoteHook]]);
        $next = $this->nextCapturing($capturedData);
        $this->createMiddleware($httpClient)->process($request, $next);

        $this->assertSame([$existingHook, $remoteHook], $capturedData['webhooks']);
    }

    // --- Escape unescaping ---

    public function testUnescapesBracesInRemoteData(): void
    {
        $request = $this->createRequest();
        $request->setAttribute('parser', $this->createParser());
        $request->setAttribute('data', ['direct' => ['url' => 'https://remote.example.com']]);

        $capturedData = [];
        $httpClient = $this->createHttpClientStub(['response' => ['text' => '\\{env.KEY\\}']]);
        $next = $this->nextCapturing($capturedData);
        $this->createMiddleware($httpClient)->process($request, $next);

        $this->assertSame('{env.KEY}', $capturedData['response']['text']);
    }
}
