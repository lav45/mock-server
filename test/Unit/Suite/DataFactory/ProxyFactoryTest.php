<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\DataFactory;

use Amp\Http\Server\Request;
use Lav45\MockServer\DataFactory\ProxyFactory;
use Lav45\MockServer\Parser\InlineParser;
use Lav45\MockServer\Parser\ParamParser;
use Lav45\MockServer\Parser\VariableParser;
use Lav45\MockServer\Test\Unit\Components\FakeHttpDriverClient;
use League\Uri\Http;
use PHPUnit\Framework\TestCase;

final class ProxyFactoryTest extends TestCase
{
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

    private function createRequest(
        string $method = 'GET',
        string $url = 'https://localhost/',
        array  $headers = [],
        string $body = '',
    ): Request {
        $request = new Request(new FakeHttpDriverClient(), $method, Http::new($url), $headers);
        $request->setAttribute('body', $body);
        return $request;
    }

    public function testCreateUsesRequestMethod(): void
    {
        $request = $this->createRequest('DELETE');
        $proxy = new ProxyFactory()->create($request, $this->createParser(), ['url' => 'https://upstream.example.com']);

        $this->assertSame('DELETE', $proxy->method->value);
    }

    public function testCreateBuildsUrlFromData(): void
    {
        $request = $this->createRequest();
        $proxy = new ProxyFactory()->create($request, $this->createParser(), ['url' => 'https://upstream.example.com/api']);

        $this->assertSame('https://upstream.example.com/api', $proxy->url->value);
    }

    public function testCreateAppendsRequestQueryToUrl(): void
    {
        $request = $this->createRequest('GET', 'https://localhost/?page=2&per-page=10');
        $proxy = new ProxyFactory()->create($request, $this->createParser(), ['url' => 'https://upstream.example.com/api']);

        $this->assertSame('https://upstream.example.com/api?page=2&per-page=10', $proxy->url->value);
    }

    public function testCreateForwardsRequestBodyWhenNoContentKey(): void
    {
        $request = $this->createRequest('POST', body: '{"key":"value"}');
        $proxy = new ProxyFactory()->create($request, $this->createParser(), ['url' => 'https://upstream.example.com']);

        $this->assertSame('{"key":"value"}', $proxy->body->value);
    }

    public function testCreateUsesContentBodyWhenContentIsString(): void
    {
        $request = $this->createRequest('POST', body: 'original body');
        $proxy = new ProxyFactory()->create($request, $this->createParser(), [
            'url' => 'https://upstream.example.com',
            'content' => 'overridden body',
        ]);

        $this->assertSame('overridden body', $proxy->body->value);
    }

    public function testCreateUsesContentBodyWhenContentIsArray(): void
    {
        $request = $this->createRequest('POST');
        $proxy = new ProxyFactory()->create($request, $this->createParser(), [
            'url' => 'https://upstream.example.com',
            'content' => ['id' => 1, 'status' => 'ok'],
        ]);

        $this->assertSame('{"id":1,"status":"ok"}', $proxy->body->value);
    }

    public function testCreateSetsJsonContentTypeWhenContentIsArray(): void
    {
        $request = $this->createRequest('POST');
        $proxy = new ProxyFactory()->create($request, $this->createParser(), [
            'url' => 'https://upstream.example.com',
            'content' => ['id' => 1],
        ]);

        $this->assertSame('application/json', $proxy->headers->toArray()['content-type']);
    }

    public function testCreateDoesNotSetJsonContentTypeWhenContentIsString(): void
    {
        $request = $this->createRequest('POST');
        $proxy = new ProxyFactory()->create($request, $this->createParser(), [
            'url' => 'https://upstream.example.com',
            'content' => 'plain text',
        ]);

        $this->assertArrayNotHasKey('content-type', $proxy->headers->toArray());
    }

    public function testCreateForwardsRequestHeaders(): void
    {
        $request = $this->createRequest(headers: ['x-custom' => ['myvalue']]);
        $proxy = new ProxyFactory()->create($request, $this->createParser(), ['url' => 'https://upstream.example.com']);

        $this->assertSame('myvalue', $proxy->headers->toArray()['x-custom']);
    }

    public function testCreateStripsFilteredHeaders(): void
    {
        $request = $this->createRequest(headers: [
            'x-keep' => ['yes'],
            'host' => ['example.com'],
        ]);
        $proxy = new ProxyFactory(['host'])->create($request, $this->createParser(), ['url' => 'https://upstream.example.com']);

        $headers = $proxy->headers->toArray();
        $this->assertArrayHasKey('x-keep', $headers);
        $this->assertArrayNotHasKey('host', $headers);
    }

    public function testCreateIncludesDataHeaders(): void
    {
        $request = $this->createRequest();
        $proxy = new ProxyFactory()->create($request, $this->createParser(), [
            'url' => 'https://upstream.example.com',
            'headers' => ['x-status' => 'active'],
        ]);

        $this->assertSame('active', $proxy->headers->toArray()['x-status']);
    }

    public function testCreateAppliesParserToContent(): void
    {
        $request = $this->createRequest('POST');
        $parser = $this->createParser(['env' => ['status' => 'active']]);
        $proxy = new ProxyFactory()->create($request, $parser, [
            'url' => 'https://upstream.example.com',
            'content' => ['status' => '{env.status}'],
        ]);

        $this->assertSame('{"status":"active"}', $proxy->body->value);
    }

    public function testCreateAppliesParserToUrl(): void
    {
        $request = $this->createRequest();
        $parser = $this->createParser(['request' => ['params' => ['path' => 'orders/42']]]);
        $proxy = new ProxyFactory()->create($request, $parser, [
            'url' => 'https://upstream.example.com/{request.params.path}',
        ]);

        $this->assertSame('https://upstream.example.com/orders/42', $proxy->url->value);
    }

    public function testCreateMergesDataAndRequestHeaders(): void
    {
        $request = $this->createRequest(headers: ['x-request-id' => ['abc123']]);
        $proxy = new ProxyFactory()->create($request, $this->createParser(), [
            'url' => 'https://upstream.example.com',
            'headers' => ['x-status' => 'active'],
        ]);

        $headers = $proxy->headers->toArray();
        $this->assertSame('active', $headers['x-status']);
        $this->assertSame('abc123', $headers['x-request-id']);
    }
}
