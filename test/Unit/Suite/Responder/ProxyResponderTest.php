<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Responder;

use Amp\Http\Client\Request as HttpClientRequest;
use Amp\Http\Client\Response as HttpClientResponse;
use Lav45\MockServer\Domain\Response\ProxyResponse;
use Lav45\MockServer\Domain\ValueObject\Body;
use Lav45\MockServer\Domain\ValueObject\HttpHeaders;
use Lav45\MockServer\Domain\ValueObject\HttpMethod;
use Lav45\MockServer\Domain\ValueObject\Url;
use Lav45\MockServer\Responder\HttpClient;
use Lav45\MockServer\Responder\ProxyResponder;
use PHPUnit\Framework\TestCase;

use function Amp\ByteStream\buffer;

final class ProxyResponderTest extends TestCase
{
    private function createProxyResponse(
        string $url = 'https://upstream.example.com',
        string $method = 'GET',
        array  $headers = [],
        string $body = '',
    ): ProxyResponse {
        return new ProxyResponse(
            url: new Url($url),
            method: new HttpMethod($method),
            headers: HttpHeaders::fromArray($headers),
            body: Body::new($body),
        );
    }

    private function createHttpClientStub(
        int    $status = 200,
        string $body = '',
        array  $headers = [],
    ): HttpClient {
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

    // --- Request forwarding ---

    public function testForwardsUrlToHttpClient(): void
    {
        $httpClient = $this->createCapturingHttpClient();
        $responder = new ProxyResponder($httpClient);

        $responder->execute($this->createProxyResponse(url: 'https://upstream.example.com/api'));

        $this->assertSame('https://upstream.example.com/api', $httpClient->calls[0]['uri']);
    }

    public function testForwardsMethodToHttpClient(): void
    {
        $httpClient = $this->createCapturingHttpClient();
        $responder = new ProxyResponder($httpClient);

        $responder->execute($this->createProxyResponse(method: 'DELETE'));

        $this->assertSame('DELETE', $httpClient->calls[0]['method']);
    }

    public function testForwardsBodyToHttpClient(): void
    {
        $httpClient = $this->createCapturingHttpClient();
        $responder = new ProxyResponder($httpClient);

        $responder->execute($this->createProxyResponse(body: '{"key":"value"}'));

        $this->assertSame('{"key":"value"}', $httpClient->calls[0]['body']);
    }

    public function testForwardsHeadersToHttpClient(): void
    {
        $httpClient = $this->createCapturingHttpClient();
        $responder = new ProxyResponder($httpClient);

        $responder->execute($this->createProxyResponse(headers: ['X-Token' => 'abc123']));

        $this->assertSame('abc123', $httpClient->calls[0]['headers']['X-Token']);
    }

    // --- Response from upstream ---

    public function testReturnsUpstreamStatus(): void
    {
        $responder = new ProxyResponder($this->createHttpClientStub(status: 404));

        $response = $responder->execute($this->createProxyResponse());

        $this->assertSame(404, $response->getStatus());
    }

    public function testReturnsUpstreamBody(): void
    {
        $responder = new ProxyResponder($this->createHttpClientStub(body: 'upstream content'));

        $response = $responder->execute($this->createProxyResponse());

        $this->assertSame('upstream content', buffer($response->getBody()));
    }

    public function testReturnsUpstreamHeaders(): void
    {
        $responder = new ProxyResponder($this->createHttpClientStub(headers: ['x-custom' => ['my-value']]));

        $response = $responder->execute($this->createProxyResponse());

        $this->assertSame('my-value', $response->getHeader('x-custom'));
    }
}
