<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Middleware;

use Amp\Http\Client\Request as HttpClientRequest;
use Amp\Http\Client\Response as HttpClientResponse;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Lav45\MockServer\DataFactory\ProxyFactory;
use Lav45\MockServer\Middleware\ProxyMiddleware;
use Lav45\MockServer\Parser\InlineParser;
use Lav45\MockServer\Parser\ParamParser;
use Lav45\MockServer\Parser\VariableParser;
use Lav45\MockServer\Responder\HttpClient;
use Lav45\MockServer\Responder\ProxyResponder;
use Lav45\MockServer\Test\Unit\Components\FakeHttpDriverClient;
use League\Uri\Http;
use PHPUnit\Framework\TestCase;

use function Amp\ByteStream\buffer;

final class ProxyMiddlewareTest extends TestCase
{
    private function createMiddleware(HttpClient $httpClient): ProxyMiddleware
    {
        return new ProxyMiddleware(new ProxyFactory(), new ProxyResponder($httpClient));
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

    private function nextReturning(int $status): \Closure
    {
        return static fn(Request $r): Response => new Response($status);
    }

    // --- Passthrough ---

    public function testPassesThroughToNextWhenResponseTypeDoesNotMatch(): void
    {
        $request = $this->createRequest();
        $request->setAttribute('responseType', 'content');
        $request->setAttribute('parser', $this->createParser());
        $request->setAttribute('data', []);

        $middleware = $this->createMiddleware($this->createHttpClientStub());
        $response = $middleware($request, $this->nextReturning(418));

        $this->assertSame(418, $response->getStatus());
    }

    public function testDoesNotCallNextWhenResponseTypeMatches(): void
    {
        $request = $this->createRequest();
        $request->setAttribute('responseType', ProxyFactory::TYPE);
        $request->setAttribute('parser', $this->createParser());
        $request->setAttribute('data', ['response' => ['url' => 'https://upstream.example.com']]);

        $middleware = $this->createMiddleware($this->createHttpClientStub());
        $response = $middleware($request, $this->nextReturning(418));

        $this->assertNotSame(418, $response->getStatus());
    }

    // --- Response from upstream ---

    public function testReturnsUpstreamStatus(): void
    {
        $request = $this->createRequest();
        $request->setAttribute('responseType', ProxyFactory::TYPE);
        $request->setAttribute('parser', $this->createParser());
        $request->setAttribute('data', ['response' => ['url' => 'https://upstream.example.com']]);

        $middleware = $this->createMiddleware($this->createHttpClientStub(status: 201));
        $response = $middleware($request, $this->nextReturning(418));

        $this->assertSame(201, $response->getStatus());
    }

    public function testReturnsUpstreamBody(): void
    {
        $request = $this->createRequest();
        $request->setAttribute('responseType', ProxyFactory::TYPE);
        $request->setAttribute('parser', $this->createParser());
        $request->setAttribute('data', ['response' => ['url' => 'https://upstream.example.com']]);

        $middleware = $this->createMiddleware($this->createHttpClientStub(body: 'upstream body'));
        $response = $middleware($request, $this->nextReturning(418));

        $this->assertSame('upstream body', buffer($response->getBody()));
    }

    // --- Attribute forwarding ---

    public function testUsesResponseKeyFromDataAttribute(): void
    {
        $request = $this->createRequest();
        $request->setAttribute('responseType', ProxyFactory::TYPE);
        $request->setAttribute('parser', $this->createParser());
        $request->setAttribute('data', [
            'env' => ['ignored' => true],
            'response' => ['url' => 'https://upstream.example.com'],
        ]);

        $middleware = $this->createMiddleware($this->createHttpClientStub(status: 200));
        $response = $middleware($request, $this->nextReturning(418));

        $this->assertSame(200, $response->getStatus());
    }

    public function testDefaultsToEmptyDataWhenResponseKeyMissing(): void
    {
        $request = $this->createRequest();
        $request->setAttribute('responseType', ProxyFactory::TYPE);
        $request->setAttribute('parser', $this->createParser());
        $request->setAttribute('data', []);

        $middleware = $this->createMiddleware($this->createHttpClientStub());

        $this->expectException(\InvalidArgumentException::class);
        $middleware($request, $this->nextReturning(418));
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
        $request->setAttribute('responseType', ProxyFactory::TYPE);
        $request->setAttribute('parser', $this->createParser());
        $request->setAttribute('data', ['response' => ['url' => 'https://upstream.example.com']]);

        $middleware = $this->createMiddleware($httpClient);
        $middleware($request, $this->nextReturning(418));

        $this->assertSame('{"key":"value"}', $httpClient->capturedBody);
    }

    public function testAppliesParserToUpstreamUrl(): void
    {
        $httpClient = new class implements HttpClient {
            public string|null $capturedUri = null;

            public function request(string $uri, string $method = 'GET', array|null $headers = null, string|null $body = null): HttpClientResponse
            {
                $this->capturedUri = $uri;
                return new HttpClientResponse('1.1', 200, 'OK', [], '', new HttpClientRequest($uri));
            }
        };

        $parser = $this->createParser(['env' => ['host' => 'https://upstream.example.com']]);

        $request = $this->createRequest();
        $request->setAttribute('responseType', ProxyFactory::TYPE);
        $request->setAttribute('parser', $parser);
        $request->setAttribute('data', ['response' => ['url' => '{env.host}/api']]);

        $middleware = $this->createMiddleware($httpClient);
        $middleware($request, $this->nextReturning(418));

        $this->assertSame('https://upstream.example.com/api', $httpClient->capturedUri);
    }
}
