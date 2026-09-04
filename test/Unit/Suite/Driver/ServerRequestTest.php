<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Driver;

use Amp\ByteStream\ReadableIterableStream;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestBody;
use Lav45\MockServer\Driver\RequestBodyTooLargeException;
use Lav45\MockServer\Driver\ServerRequest;
use Lav45\MockServer\Test\Unit\Components\FakeHttpDriverClient;
use League\Uri\Http;
use PHPUnit\Framework\TestCase;

final class ServerRequestTest extends TestCase
{
    private function createRequest(
        string $method = 'GET',
        string $url = 'https://localhost/',
        array  $headers = [],
        string $body = '',
        int    $maxRequestBodySize = \PHP_INT_MAX,
    ): ServerRequest {
        $request = new Request(new FakeHttpDriverClient(), $method, Http::new($url), $headers, new RequestBody($body));
        return new ServerRequest($request, $maxRequestBodySize);
    }

    private function createStreamedRequest(string $body, int $maxRequestBodySize): ServerRequest
    {
        $requestBody = new RequestBody(new ReadableIterableStream(\str_split($body, 2)));
        $request = new Request(new FakeHttpDriverClient(), 'POST', Http::new('https://localhost/'), [], $requestBody);
        return new ServerRequest($request, $maxRequestBodySize);
    }

    public function testGetBodyThrowsWhenBodyExceedsMaxRequestBodySize(): void
    {
        $request = $this->createStreamedRequest('payload', maxRequestBodySize: 4);

        $this->expectException(RequestBodyTooLargeException::class);
        $request->getBody();
    }

    public function testGetBodyReturnsBodyOfMaxRequestBodySize(): void
    {
        $request = $this->createStreamedRequest('payload', maxRequestBodySize: 7);

        $this->assertSame('payload', $request->getBody());
    }

    public function testGetMethod(): void
    {
        $this->assertSame('PATCH', $this->createRequest('PATCH')->getMethod());
    }

    public function testGetPath(): void
    {
        $this->assertSame('/api/users', $this->createRequest(url: 'https://localhost/api/users?x=1')->getPath());
    }

    public function testGetQueryParameters(): void
    {
        $request = $this->createRequest(url: 'https://localhost/?id=1&id=2&type=test');

        $this->assertSame(['id' => ['1', '2'], 'type' => ['test']], $request->getQueryParameters());
    }

    public function testGetHeaders(): void
    {
        $request = $this->createRequest(headers: ['accept' => ['text/html', 'application/json']]);

        $this->assertSame(['text/html', 'application/json'], $request->getHeaders()['accept']);
    }

    public function testGetHeader(): void
    {
        $request = $this->createRequest(headers: ['x-token' => ['secret']]);

        $this->assertSame('secret', $request->getHeader('x-token'));
    }

    public function testGetHeaderReturnsNullWhenAbsent(): void
    {
        $this->assertNull($this->createRequest()->getHeader('x-missing'));
    }

    public function testGetBodyBuffersOnce(): void
    {
        $request = $this->createRequest(body: 'raw payload');

        $this->assertSame('raw payload', $request->getBody());
        $this->assertSame('raw payload', $request->getBody());
    }

    public function testGetParsedBodyReturnsEmptyForEmptyBody(): void
    {
        $this->assertSame([], $this->createRequest()->getParsedBody());
    }

    public function testGetParsedBodyParsesUrlencoded(): void
    {
        $request = $this->createRequest(
            method: 'POST',
            headers: ['content-type' => ['application/x-www-form-urlencoded']],
            body: 'name=John&age=12',
        );

        $parsed = $request->getParsedBody();
        $this->assertSame('John', $parsed['name']);
        $this->assertSame('12', $parsed['age']);
    }

    public function testGetParsedBodyParsesMultipart(): void
    {
        $body = "--FB\r\nContent-Disposition: form-data; name=\"name\"\r\n\r\nJohn\r\n"
            . "--FB\r\nContent-Disposition: form-data; name=\"age\"\r\n\r\n12\r\n--FB--\r\n";

        $request = $this->createRequest(
            method: 'POST',
            headers: ['content-type' => ['multipart/form-data; boundary=FB']],
            body: $body,
        );

        $parsed = $request->getParsedBody();
        $this->assertSame('John', $parsed['name']);
        $this->assertSame('12', $parsed['age']);
    }

    public function testGetParsedBodyParsesMultipartWithRepeatedFieldName(): void
    {
        $body = "--FB\r\nContent-Disposition: form-data; name=\"tags\"\r\n\r\na\r\n"
            . "--FB\r\nContent-Disposition: form-data; name=\"tags\"\r\n\r\nb\r\n--FB--\r\n";

        $request = $this->createRequest(
            method: 'POST',
            headers: ['content-type' => ['multipart/form-data; boundary=FB']],
            body: $body,
        );

        $parsed = $request->getParsedBody();
        $this->assertSame(['a', 'b'], $parsed['tags']);
    }

    public function testAttributesAreStoredAndRetrieved(): void
    {
        $request = $this->createRequest();

        $this->assertFalse($request->hasAttribute('data'));
        $this->assertNull($request->getAttribute('data'));

        $request->setAttribute('data', ['key' => 'value']);

        $this->assertTrue($request->hasAttribute('data'));
        $this->assertSame(['key' => 'value'], $request->getAttribute('data'));
    }
}
