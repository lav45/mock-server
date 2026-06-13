<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\DataFactory;

use Amp\Http\Server\Request;
use Lav45\MockServer\DataFactory\DataBuilder;
use Lav45\MockServer\DataFactory\DirectFactory;
use Lav45\MockServer\Domain\Direct;
use Lav45\MockServer\Test\Unit\Components\FakeHttpDriverClient;
use League\Uri\Http;
use PHPUnit\Framework\TestCase;

final class DirectFactoryTest extends TestCase
{
    private function create(Request $request, array $direct, array $filterHeaders = []): Direct
    {
        return new DirectFactory(new DataBuilder($filterHeaders))->create($request, ['direct' => $direct]);
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
        $request = $this->createRequest('PUT');
        $direct = $this->create($request, ['url' => 'https://upstream.example.com']);

        $this->assertSame('PUT', $direct->method->value);
    }

    public function testCreateBuildsUrlFromData(): void
    {
        $request = $this->createRequest();
        $direct = $this->create($request, ['url' => 'https://upstream.example.com/api']);

        $this->assertSame('https://upstream.example.com/api', $direct->url->value);
    }

    public function testCreateAppendsRequestQueryToUrl(): void
    {
        $request = $this->createRequest('GET', 'https://localhost/?page=2&per-page=10');
        $direct = $this->create($request, ['url' => 'https://upstream.example.com/api']);

        $this->assertSame('https://upstream.example.com/api?page=2&per-page=10', $direct->url->value);
    }

    public function testCreateForwardsRequestBody(): void
    {
        $request = $this->createRequest('POST', body: '{"key":"value"}');
        $direct = $this->create($request, ['url' => 'https://upstream.example.com']);

        $this->assertSame('{"key":"value"}', $direct->body->value);
    }

    public function testCreateForwardsRequestHeaders(): void
    {
        $request = $this->createRequest(headers: ['x-custom' => ['myvalue']]);
        $direct = $this->create($request, ['url' => 'https://upstream.example.com']);

        $this->assertSame('myvalue', $direct->headers->toArray()['x-custom']);
    }

    public function testCreateStripsFilteredHeaders(): void
    {
        $request = $this->createRequest(headers: [
            'x-keep' => ['yes'],
            'host' => ['example.com'],
        ]);
        $direct = $this->create($request, ['url' => 'https://upstream.example.com'], ['host']);

        $headers = $direct->headers->toArray();
        $this->assertArrayHasKey('x-keep', $headers);
        $this->assertArrayNotHasKey('host', $headers);
    }

    public function testCreateIncludesDataHeaders(): void
    {
        $request = $this->createRequest();
        $direct = $this->create($request, [
            'url' => 'https://upstream.example.com',
            'headers' => ['x-status' => 'active'],
        ]);

        $this->assertSame('active', $direct->headers->toArray()['x-status']);
    }

    public function testCreateMergesDataAndRequestHeaders(): void
    {
        $request = $this->createRequest(headers: ['x-request-id' => ['abc123']]);
        $direct = $this->create($request, [
            'url' => 'https://upstream.example.com',
            'headers' => ['x-status' => 'active'],
        ]);

        $headers = $direct->headers->toArray();
        $this->assertSame('active', $headers['x-status']);
        $this->assertSame('abc123', $headers['x-request-id']);
    }
}
