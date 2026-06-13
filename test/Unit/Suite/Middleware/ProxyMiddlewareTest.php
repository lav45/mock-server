<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Middleware;

use Amp\Http\Client\Request as HttpClientRequest;
use Amp\Http\Client\Response as HttpClientResponse;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Lav45\MockServer\DataFactory\DataBuilder;
use Lav45\MockServer\DataFactory\ProxyFactory;
use Lav45\MockServer\Middleware\MiddlewareHandler;
use Lav45\MockServer\Middleware\ProxyMiddleware;
use Lav45\MockServer\Responder\HttpClient;
use Lav45\MockServer\Responder\ProxyResponder;
use Lav45\MockServer\Test\Unit\Components\CallableHandler;
use Lav45\MockServer\Test\Unit\Components\FakeHttpDriverClient;
use League\Uri\Http;
use PHPUnit\Framework\TestCase;

use function Amp\ByteStream\buffer;

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
    ): Request {
        $request = new Request(new FakeHttpDriverClient(), $method, Http::new($url));
        $request->setAttribute('body', $body);
        return $request;
    }

    private function createHttpClientStub(int $status = 200, string $body = '', array $headers = []): HttpClient
    {
        return new readonly class ($status, $body, $headers) implements HttpClient {
            public function __construct(
                private int    $status,
                private string $body,
                private array  $headers,
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
                    $this->headers,
                    $this->body,
                    new HttpClientRequest($uri),
                );
            }
        };
    }

    private function nextReturning(int $status): MiddlewareHandler
    {
        return new CallableHandler(static fn(Request $r): Response => new Response($status));
    }

    // --- Passthrough ---

    public function testPassesThroughToNextWhenResponseTypeDoesNotMatch(): void
    {
        $request = $this->createRequest();
        $request->setAttribute('data', ['response' => ['type' => 'content']]);

        $middleware = $this->createMiddleware($this->createHttpClientStub());
        $response = $middleware->process($request, $this->nextReturning(418));

        $this->assertSame(418, $response->getStatus());
    }

    public function testDoesNotCallNextWhenResponseTypeMatches(): void
    {
        $request = $this->createRequest();
        $request->setAttribute('data', ['response' => ['type' => 'proxy', 'url' => 'https://upstream.example.com']]);

        $middleware = $this->createMiddleware($this->createHttpClientStub());
        $response = $middleware->process($request, $this->nextReturning(418));

        $this->assertNotSame(418, $response->getStatus());
    }

    // --- Response from upstream ---

    public function testReturnsUpstreamStatus(): void
    {
        $request = $this->createRequest();
        $request->setAttribute('data', ['response' => ['type' => 'proxy', 'url' => 'https://upstream.example.com']]);

        $middleware = $this->createMiddleware($this->createHttpClientStub(status: 201));
        $response = $middleware->process($request, $this->nextReturning(418));

        $this->assertSame(201, $response->getStatus());
    }

    public function testReturnsUpstreamBody(): void
    {
        $request = $this->createRequest();
        $request->setAttribute('data', ['response' => ['type' => 'proxy', 'url' => 'https://upstream.example.com']]);

        $middleware = $this->createMiddleware($this->createHttpClientStub(body: 'upstream body'));
        $response = $middleware->process($request, $this->nextReturning(418));

        $this->assertSame('upstream body', buffer($response->getBody()));
    }

    // --- Attribute forwarding ---

    public function testUsesResponseKeyFromDataAttribute(): void
    {
        $request = $this->createRequest();
        $request->setAttribute('data', [
            'env' => ['ignored' => true],
            'response' => ['type' => 'proxy', 'url' => 'https://upstream.example.com'],
        ]);

        $middleware = $this->createMiddleware($this->createHttpClientStub(status: 200));
        $response = $middleware->process($request, $this->nextReturning(418));

        $this->assertSame(200, $response->getStatus());
    }

    public function testSkipsWhenResponseKeyMissing(): void
    {
        $request = $this->createRequest();
        $request->setAttribute('data', ['response' => ['type' => 'content']]);

        $middleware = $this->createMiddleware($this->createHttpClientStub());

        $response = $middleware->process($request, $this->nextReturning(418));
        $this->assertSame(418, $response->getStatus());
    }

    // --- Request forwarding ---

    public function testForwardsRequestBodyToUpstream(): void
    {
        $httpClient = new class implements HttpClient {
            public string|null $capturedBody = null;

            public function request(string $uri, string $method = 'GET', array|null $headers = null, string|null $body = null): HttpClientResponse
            {
                $this->capturedBody = $body;
                return new HttpClientResponse('1.1', 200, 'OK', [], '', new HttpClientRequest($uri));
            }
        };

        $request = $this->createRequest(body: '{"key":"value"}');
        $request->setAttribute('data', ['response' => ['type' => 'proxy', 'url' => 'https://upstream.example.com']]);

        $middleware = $this->createMiddleware($httpClient);
        $middleware->process($request, $this->nextReturning(418));

        $this->assertSame('{"key":"value"}', $httpClient->capturedBody);
    }
}
