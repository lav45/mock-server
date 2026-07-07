<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Extension\Proxy;

use Lav45\MockServer\Domain\Response\ProxyResponse;
use Lav45\MockServer\Domain\ValueObject\Body;
use Lav45\MockServer\Domain\ValueObject\HttpHeaders;
use Lav45\MockServer\Domain\ValueObject\HttpMethod;
use Lav45\MockServer\Domain\ValueObject\Url;
use Lav45\MockServer\Extension\Proxy\ProxyResponder;
use Lav45\MockServer\Test\Unit\Components\FakeHttpClient;
use PHPUnit\Framework\TestCase;

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

    // --- Request forwarding ---

    public function testForwardsUrlToHttpClient(): void
    {
        $httpClient = new FakeHttpClient();
        $responder = new ProxyResponder($httpClient);

        $responder->execute($this->createProxyResponse(url: 'https://upstream.example.com/api'));

        $this->assertSame('https://upstream.example.com/api', $httpClient->calls[0]['uri']);
    }

    public function testForwardsMethodToHttpClient(): void
    {
        $httpClient = new FakeHttpClient();
        $responder = new ProxyResponder($httpClient);

        $responder->execute($this->createProxyResponse(method: 'DELETE'));

        $this->assertSame('DELETE', $httpClient->calls[0]['method']);
    }

    public function testForwardsBodyToHttpClient(): void
    {
        $httpClient = new FakeHttpClient();
        $responder = new ProxyResponder($httpClient);

        $responder->execute($this->createProxyResponse(body: '{"key":"value"}'));

        $this->assertSame('{"key":"value"}', $httpClient->calls[0]['body']->stream->read());
    }

    public function testForwardsHeadersToHttpClient(): void
    {
        $httpClient = new FakeHttpClient();
        $responder = new ProxyResponder($httpClient);

        $responder->execute($this->createProxyResponse(headers: ['X-Token' => 'abc123']));

        $this->assertSame('abc123', $httpClient->calls[0]['headers']['X-Token']);
    }

    // --- Response from upstream ---

    public function testReturnsUpstreamStatus(): void
    {
        $responder = new ProxyResponder(new FakeHttpClient(status: 404));

        $response = $responder->execute($this->createProxyResponse());

        $this->assertSame(404, $response->getStatus());
    }

    public function testReturnsUpstreamBody(): void
    {
        $responder = new ProxyResponder(new FakeHttpClient(body: 'upstream content'));

        $response = $responder->execute($this->createProxyResponse());

        $this->assertSame('upstream content', $response->getBody()->stream->read());
    }

    public function testReturnsUpstreamHeaders(): void
    {
        $responder = new ProxyResponder(new FakeHttpClient(headers: ['x-custom' => ['my-value']]));

        $response = $responder->execute($this->createProxyResponse());

        $this->assertSame('my-value', $response->getHeader('x-custom'));
    }
}
