<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\DataFactory;

use Lav45\MockServer\DataFactory\DataBuilder;
use Lav45\MockServer\DataFactory\ProxyFactory;
use Lav45\MockServer\Engine\Http\ServerRequest;
use Lav45\MockServer\Test\Unit\Components\FakeServerRequest;
use PHPUnit\Framework\TestCase;

final class ProxyFactoryTest extends TestCase
{
    private function createRequest(
        string $method = 'GET',
        string $url = 'https://localhost/',
        array  $headers = [],
        string $body = '',
    ): ServerRequest {
        return new FakeServerRequest($method, $url, $headers, $body);
    }

    public function testHasMatchesProxyType(): void
    {
        $this->assertTrue(new ProxyFactory(new DataBuilder())->has(['type' => 'proxy']));
    }

    public function testHasDoesNotMatchWhenTypeMissing(): void
    {
        $this->assertFalse(new ProxyFactory(new DataBuilder())->has([]));
    }

    public function testHasDoesNotMatchOtherType(): void
    {
        $this->assertFalse(new ProxyFactory(new DataBuilder())->has(['type' => 'content']));
    }

    public function testCreateUsesRequestMethod(): void
    {
        $request = $this->createRequest('DELETE');
        $proxy = new ProxyFactory(new DataBuilder())->create($request, ['url' => 'https://upstream.example.com']);

        $this->assertSame('DELETE', $proxy->method->value);
    }

    public function testCreateBuildsUrlFromData(): void
    {
        $request = $this->createRequest();
        $proxy = new ProxyFactory(new DataBuilder())->create($request, ['url' => 'https://upstream.example.com/api']);

        $this->assertSame('https://upstream.example.com/api', $proxy->url->value);
    }

    public function testCreateAppendsRequestQueryToUrl(): void
    {
        $request = $this->createRequest('GET', 'https://localhost/?page=2&per-page=10');
        $proxy = new ProxyFactory(new DataBuilder())->create($request, ['url' => 'https://upstream.example.com/api']);

        $this->assertSame('https://upstream.example.com/api?page=2&per-page=10', $proxy->url->value);
    }

    public function testCreateForwardsRequestBodyWhenNoContentKey(): void
    {
        $request = $this->createRequest('POST', body: '{"key":"value"}');
        $proxy = new ProxyFactory(new DataBuilder())->create($request, ['url' => 'https://upstream.example.com']);

        $this->assertSame('{"key":"value"}', $proxy->body->toString());
    }

    public function testCreateSetsJsonContentTypeWhenForwardedBodyIsJson(): void
    {
        $request = $this->createRequest('POST', body: '{"key":"value"}');
        $proxy = new ProxyFactory(new DataBuilder())->create($request, ['url' => 'https://upstream.example.com']);

        $this->assertSame('application/json', $proxy->headers->toArray()['content-type']);
    }

    public function testCreateOverridesRequestContentTypeWhenContentIsJson(): void
    {
        $request = $this->createRequest('POST', headers: ['content-type' => ['text/plain']]);
        $proxy = new ProxyFactory(new DataBuilder())->create($request, [
            'url' => 'https://upstream.example.com',
            'content' => ['id' => 1],
        ]);

        $this->assertSame('application/json', $proxy->headers->toArray()['content-type']);
    }

    public function testCreateUsesContentBodyWhenContentIsString(): void
    {
        $request = $this->createRequest('POST', body: 'original body');
        $proxy = new ProxyFactory(new DataBuilder())->create($request, [
            'url' => 'https://upstream.example.com',
            'content' => 'overridden body',
        ]);

        $this->assertSame('overridden body', $proxy->body->toString());
    }

    public function testCreateUsesContentBodyWhenContentIsArray(): void
    {
        $request = $this->createRequest('POST');
        $proxy = new ProxyFactory(new DataBuilder())->create($request, [
            'url' => 'https://upstream.example.com',
            'content' => ['id' => 1, 'status' => 'ok'],
        ]);

        $this->assertSame('{"id":1,"status":"ok"}', $proxy->body->toString());
    }

    public function testCreateSetsJsonContentTypeWhenContentIsArray(): void
    {
        $request = $this->createRequest('POST');
        $proxy = new ProxyFactory(new DataBuilder())->create($request, [
            'url' => 'https://upstream.example.com',
            'headers' => ['content-type' => 'application/json'],
            'content' => ['id' => 1],
        ]);

        $this->assertSame('application/json', $proxy->headers->toArray()['content-type']);
    }

    public function testCreateDoesNotSetJsonContentTypeWhenContentIsString(): void
    {
        $request = $this->createRequest('POST');
        $proxy = new ProxyFactory(new DataBuilder())->create($request, [
            'url' => 'https://upstream.example.com',
            'content' => 'plain text',
        ]);

        $this->assertArrayNotHasKey('content-type', $proxy->headers->toArray());
    }

    public function testCreateForwardsRequestHeaders(): void
    {
        $request = $this->createRequest(headers: ['x-custom' => ['myvalue']]);
        $proxy = new ProxyFactory(new DataBuilder())->create($request, ['url' => 'https://upstream.example.com']);

        $this->assertSame('myvalue', $proxy->headers->toArray()['x-custom']);
    }

    public function testCreateStripsFilteredHeaders(): void
    {
        $request = $this->createRequest(headers: [
            'x-keep' => ['yes'],
            'host' => ['example.com'],
        ]);
        $proxy = new ProxyFactory(new DataBuilder(['host']))->create($request, ['url' => 'https://upstream.example.com']);

        $headers = $proxy->headers->toArray();
        $this->assertArrayHasKey('x-keep', $headers);
        $this->assertArrayNotHasKey('host', $headers);
    }

    public function testCreateIncludesDataHeaders(): void
    {
        $request = $this->createRequest();
        $proxy = new ProxyFactory(new DataBuilder())->create($request, [
            'url' => 'https://upstream.example.com',
            'headers' => ['x-status' => 'active'],
        ]);

        $this->assertSame('active', $proxy->headers->toArray()['x-status']);
    }

    public function testCreateMergesDataAndRequestHeaders(): void
    {
        $request = $this->createRequest(headers: ['x-request-id' => ['abc123']]);
        $proxy = new ProxyFactory(new DataBuilder())->create($request, [
            'url' => 'https://upstream.example.com',
            'headers' => ['x-status' => 'active'],
        ]);

        $headers = $proxy->headers->toArray();
        $this->assertSame('active', $headers['x-status']);
        $this->assertSame('abc123', $headers['x-request-id']);
    }
}
