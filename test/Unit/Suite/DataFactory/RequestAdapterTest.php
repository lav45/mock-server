<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\DataFactory;

use Lav45\MockServer\DataFactory\RequestAdapter;
use Lav45\MockServer\Engine\Http\ServerRequest;
use Lav45\MockServer\Test\Unit\Components\FakeServerRequest;
use PHPUnit\Framework\TestCase;

final class RequestAdapterTest extends TestCase
{
    private function createRequest(
        string $url = 'https://localhost/',
        array  $headers = [],
        string $body = '',
        array  $parsedBody = [],
    ): ServerRequest {
        return new FakeServerRequest('GET', $url, $headers, $body, $parsedBody);
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

    public function testGetDataUsesParsedBodyWhenBodyIsNotJson(): void
    {
        $adapter = new RequestAdapter($this->createRequest(
            headers: ['content-type' => ['multipart/form-data; boundary=FB']],
            body: 'raw multipart payload',
            parsedBody: ['name' => 'John', 'age' => '12'],
        ));
        $data = $adapter->getData();
        $this->assertSame('John', $data['name']);
        $this->assertSame('12', $data['age']);
    }
}
