<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Extension\Direct;

use Lav45\MockServer\DataFactory\DataBuilder;
use Lav45\MockServer\Engine\Http\RequestHandler;
use Lav45\MockServer\Engine\Http\ServerRequest;
use Lav45\MockServer\Engine\Http\ServerResponse;
use Lav45\MockServer\Engine\HttpClient;
use Lav45\MockServer\Extension\Direct\DirectFactory;
use Lav45\MockServer\Extension\Direct\DirectHandler;
use Lav45\MockServer\Extension\Direct\DirectMiddleware;
use Lav45\MockServer\Parser\InlineParser;
use Lav45\MockServer\Parser\ParamParser;
use Lav45\MockServer\Parser\VariableParser;
use Lav45\MockServer\Test\Unit\Components\CallableHandler;
use Lav45\MockServer\Test\Unit\Components\FakeHttpClient;
use Lav45\MockServer\Test\Unit\Components\FakeLogger;
use Lav45\MockServer\Test\Unit\Components\FakeServerRequest;
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

    private function createRequest(string $method = 'GET'): ServerRequest
    {
        return new FakeServerRequest($method, 'https://localhost/api/123');
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

    private function nextCapturing(array &$capturedData): RequestHandler
    {
        return new CallableHandler(static function (ServerRequest $request) use (&$capturedData): ServerResponse {
            $capturedData = $request->getAttribute('data');
            return new ServerResponse(200);
        });
    }

    // --- Passthrough ---

    public function testPassesThroughToNextWhenDirectKeyMissing(): void
    {
        $request = $this->createRequest();
        $request->setAttribute('parser', $this->createParser());
        $request->setAttribute('data', ['response' => ['text' => 'original']]);

        $called = false;
        $next = function (ServerRequest $r) use (&$called): ServerResponse {
            $called = true;
            return new ServerResponse(418);
        };

        $middleware = $this->createMiddleware(new FakeHttpClient(body: []));
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
        $next = function (ServerRequest $r) use (&$called): ServerResponse {
            $called = true;
            return new ServerResponse(200);
        };

        $middleware = $this->createMiddleware(new FakeHttpClient(body: []));
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
        $httpClient = new FakeHttpClient(body: ['response' => ['status' => 201, 'json' => ['id' => 42]]]);
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
        $httpClient = new FakeHttpClient(body: ['response' => ['text' => 'from remote']]);
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

        $httpClient = new FakeHttpClient(body: ['response' => ['text' => 'from remote']]);
        $middleware = $this->createMiddleware($httpClient, $logger);
        $middleware->process($request, new CallableHandler(fn() => new ServerResponse(200)));

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

        $httpClient = new FakeHttpClient(body: ['response' => ['text' => 'from remote']]);
        $middleware = $this->createMiddleware($httpClient, $logger);
        $middleware->process($request, new CallableHandler(fn() => new ServerResponse(200)));

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
        $httpClient = new FakeHttpClient(body: []);
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
        $httpClient = new FakeHttpClient(body: ['webhooks' => $remoteHooks]);
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
        $httpClient = new FakeHttpClient(body: ['webhooks' => [$remoteHook]]);
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
        $httpClient = new FakeHttpClient(body: ['response' => ['text' => '\\{env.KEY\\}']]);
        $next = $this->nextCapturing($capturedData);
        $this->createMiddleware($httpClient)->process($request, $next);

        $this->assertSame('{env.KEY}', $capturedData['response']['text']);
    }
}
