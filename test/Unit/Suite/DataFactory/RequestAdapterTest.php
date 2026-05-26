<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\DataFactory;

use Amp\Http\Server\Request;
use Amp\Http\Server\RequestBody;
use Lav45\MockServer\DataFactory\RequestAdapter;
use Lav45\MockServer\Test\Unit\Components\FakeHttpDriverClient;
use League\Uri\Http;
use PHPUnit\Framework\TestCase;

final class RequestAdapterTest extends TestCase
{
    private function createRequest(
        string $url = 'https://localhost/',
        array  $headers = [],
        string $body = '',
    ): Request {
        $request = new Request(new FakeHttpDriverClient(), 'GET', Http::new($url), $headers);
        $request->setAttribute('body', $body);
        return $request;
    }

    public function testGetQueryReturnsEmptyForNoParams(): void
    {
        $adapter = new RequestAdapter($this->createRequest());
        $this->assertSame([], $adapter->getQuery());
    }

    public function testGetQueryParsesSingleParams(): void
    {
        $adapter = new RequestAdapter($this->createRequest(url: 'https://localhost/?id=5&type=test'));
        $this->assertSame(['id' => '5', 'type' => 'test'], $adapter->getQuery());
    }

    public function testGetQueryKeepsArrayForRepeatedParam(): void
    {
        $adapter = new RequestAdapter($this->createRequest(url: 'https://localhost/?id=1&id=2'));
        $this->assertSame(['id' => ['1', '2']], $adapter->getQuery());
    }

    public function testGetHeadersReturnsSingleValue(): void
    {
        $adapter = new RequestAdapter($this->createRequest(headers: ['x-custom' => ['myvalue']]));
        $this->assertSame('myvalue', $adapter->getHeaders()['x-custom']);
    }

    public function testGetHeadersKeepsArrayForMultipleValues(): void
    {
        $adapter = new RequestAdapter($this->createRequest(headers: ['accept' => ['text/html', 'application/json']]));
        $this->assertSame(['text/html', 'application/json'], $adapter->getHeaders()['accept']);
    }

    public function testGetBodyReturnsEmptyByDefault(): void
    {
        $adapter = new RequestAdapter($this->createRequest());
        $this->assertSame('', $adapter->getBody());
    }

    public function testGetBodyReturnsRequestBody(): void
    {
        $adapter = new RequestAdapter($this->createRequest(body: 'raw body content'));
        $this->assertSame('raw body content', $adapter->getBody());
    }

    public function testGetBodyBuffersStreamWhenAttributeNotPreset(): void
    {
        $request = new Request(new FakeHttpDriverClient(), 'GET', Http::new('https://localhost/'), [], new RequestBody('buffered content'));
        $adapter = new RequestAdapter($request);
        $this->assertSame('buffered content', $adapter->getBody());
    }

    public function testGetDataReturnsEmptyForEmptyBody(): void
    {
        $adapter = new RequestAdapter($this->createRequest());
        $this->assertSame([], $adapter->getData());
    }

    public function testGetDataParsesJsonBody(): void
    {
        $adapter = new RequestAdapter($this->createRequest(
            headers: ['content-type' => ['application/json']],
            body: '{"id":1,"name":"test"}',
        ));
        $this->assertSame(['id' => 1, 'name' => 'test'], $adapter->getData());
    }

    public function testGetDataParsesUrlencodedBody(): void
    {
        $adapter = new RequestAdapter($this->createRequest(
            headers: ['content-type' => ['application/x-www-form-urlencoded']],
            body: 'name=John&age=12',
        ));
        $data = $adapter->getData();
        $this->assertSame('John', $data['name']);
        $this->assertSame('12', $data['age']);
    }

    public function testGetDataParsesMultipartBody(): void
    {
        $body = "--FB\r\nContent-Disposition: form-data; name=\"name\"\r\n\r\nJohn\r\n"
            . "--FB\r\nContent-Disposition: form-data; name=\"age\"\r\n\r\n12\r\n--FB--\r\n";

        $adapter = new RequestAdapter($this->createRequest(
            headers: ['content-type' => ['multipart/form-data; boundary=FB']],
            body: $body,
        ));
        $data = $adapter->getData();
        $this->assertSame('John', $data['name']);
        $this->assertSame('12', $data['age']);
    }
}
