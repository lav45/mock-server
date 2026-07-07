<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Extension\Proxy;

use Lav45\MockServer\DataFactory\DataBuilder;
use Lav45\MockServer\Engine\Http\RequestHandler;
use Lav45\MockServer\Engine\Http\ServerRequest;
use Lav45\MockServer\Engine\Http\ServerResponse;
use Lav45\MockServer\Engine\HttpClient;
use Lav45\MockServer\Extension\Proxy\ProxyFactory;
use Lav45\MockServer\Extension\Proxy\ProxyMiddleware;
use Lav45\MockServer\Extension\Proxy\ProxyResponder;
use Lav45\MockServer\Test\Unit\Components\CallableHandler;
use Lav45\MockServer\Test\Unit\Components\FakeHttpClient;
use Lav45\MockServer\Test\Unit\Components\FakeServerRequest;
use PHPUnit\Framework\TestCase;

final class ProxyMiddlewareTest extends TestCase
{
    private function createMiddleware(HttpClient $httpClient): ProxyMiddleware
    {
        return new ProxyMiddleware(new ProxyFactory(new DataBuilder()), new ProxyResponder($httpClient));
    }

    private function createRequest(
        string $method = 'GET',
        string $url = 'https://localhost/',
        string $body = '',
    ): ServerRequest {
        return new FakeServerRequest($method, $url, body: $body);
    }

    private function nextReturning(int $status): RequestHandler
    {
        return new CallableHandler(static fn(ServerRequest $r): ServerResponse => new ServerResponse($status));
    }

    // --- Passthrough ---

    public function testPassesThroughToNextWhenResponseTypeDoesNotMatch(): void
    {
        $request = $this->createRequest();
        $request->setAttribute('data', ['response' => ['type' => 'content']]);

        $middleware = $this->createMiddleware(new FakeHttpClient());
        $response = $middleware->process($request, $this->nextReturning(418));

        $this->assertSame(418, $response->getStatus());
    }

    public function testDoesNotCallNextWhenResponseTypeMatches(): void
    {
        $request = $this->createRequest();
        $request->setAttribute('data', ['response' => ['type' => 'proxy', 'url' => 'https://upstream.example.com']]);

        $middleware = $this->createMiddleware(new FakeHttpClient());
        $response = $middleware->process($request, $this->nextReturning(418));

        $this->assertNotSame(418, $response->getStatus());
    }

    // --- Response from upstream ---

    public function testReturnsUpstreamStatus(): void
    {
        $request = $this->createRequest();
        $request->setAttribute('data', ['response' => ['type' => 'proxy', 'url' => 'https://upstream.example.com']]);

        $middleware = $this->createMiddleware(new FakeHttpClient(status: 201));
        $response = $middleware->process($request, $this->nextReturning(418));

        $this->assertSame(201, $response->getStatus());
    }

    public function testReturnsUpstreamBody(): void
    {
        $request = $this->createRequest();
        $request->setAttribute('data', ['response' => ['type' => 'proxy', 'url' => 'https://upstream.example.com']]);

        $middleware = $this->createMiddleware(new FakeHttpClient(body: 'upstream body'));
        $response = $middleware->process($request, $this->nextReturning(418));

        $this->assertSame('upstream body', $response->getBody()->stream->read());
    }

    // --- Attribute forwarding ---

    public function testUsesResponseKeyFromDataAttribute(): void
    {
        $request = $this->createRequest();
        $request->setAttribute('data', [
            'env' => ['ignored' => true],
            'response' => ['type' => 'proxy', 'url' => 'https://upstream.example.com'],
        ]);

        $middleware = $this->createMiddleware(new FakeHttpClient(status: 200));
        $response = $middleware->process($request, $this->nextReturning(418));

        $this->assertSame(200, $response->getStatus());
    }

    public function testSkipsWhenResponseKeyMissing(): void
    {
        $request = $this->createRequest();
        $request->setAttribute('data', ['response' => ['type' => 'content']]);

        $middleware = $this->createMiddleware(new FakeHttpClient());

        $response = $middleware->process($request, $this->nextReturning(418));
        $this->assertSame(418, $response->getStatus());
    }

    // --- Request forwarding ---

    public function testForwardsRequestBodyToUpstream(): void
    {
        $httpClient = new FakeHttpClient();

        $request = $this->createRequest(body: '{"key":"value"}');
        $request->setAttribute('data', ['response' => ['type' => 'proxy', 'url' => 'https://upstream.example.com']]);

        $middleware = $this->createMiddleware($httpClient);
        $middleware->process($request, $this->nextReturning(418));

        $this->assertSame('{"key":"value"}', $httpClient->calls[0]['body']->stream->read());
    }
}
