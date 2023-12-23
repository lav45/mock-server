<?php declare(strict_types=1);

namespace lav45\MockServer\test\suite\Mock\Response;

use lav45\MockServer\HttpClient;
use PHPUnit\Framework\TestCase;

/**
 * @see \lav45\MockServer\Mock\Response\Proxy
 */
class ProxyTest extends TestCase
{
    private HttpClient $HttpClient;

    protected function setUp(): void
    {
        $this->HttpClient = (new HttpClient())->build();
    }

    public function testPost(): void
    {
        $data = ['text' => 'OK'];

        $response = $this->HttpClient->request(
            uri: 'http://127.0.0.1/response/proxy/storage',
            method: 'POST',
            body: json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            headers: ['content-type' => 'application/json']
        );
        $this->assertEquals(200, $response->getStatus());

        $content = $this->getStorageData();

        $this->assertEquals('POST', $content[0]['method']);
        $this->assertEquals([], $content[0]['get']);
        $this->assertEquals($data, $content[0]['post']);

        $this->assertArrayHasKey('content-type', $content[0]['headers']);
        $this->assertEquals('application/json', $content[0]['headers']['content-type'][0]);

        $this->assertArrayHasKey('authorization', $content[0]['headers']);
        $this->assertEquals('Bearer eyJhbGciOiJSUzI1NiJ9', $content[0]['headers']['authorization'][0]);
    }

    public function testGet(): void
    {
        $get = ['id' => 100];

        $response = $this->HttpClient->request(
            uri: 'http://127.0.0.1/response/proxy/storage',
            query: $get,
            headers: ['content-type' => 'application/json']
        );
        $this->assertEquals(200, $response->getStatus());

        $content = $this->getStorageData();

        $this->assertEquals('GET', $content[0]['method']);
        $this->assertEquals($get, $content[0]['get']);
        $this->assertEquals([], $content[0]['post']);

        $this->assertArrayHasKey('content-type', $content[0]['headers']);
        $this->assertEquals('application/json', $content[0]['headers']['content-type'][0]);

        $this->assertArrayHasKey('authorization', $content[0]['headers']);
        $this->assertEquals('Bearer eyJhbGciOiJSUzI1NiJ9', $content[0]['headers']['authorization'][0]);
    }

    private function getStorageData(): array
    {
        $response = $this->HttpClient->request('http://127.0.0.1:8000/__storage');
        $content = $response->getBody()->buffer();
        return json_decode($content, true);
    }

    public function testArrayContent(): void
    {
        $response = $this->HttpClient->request(
            uri: 'http://127.0.0.1/response/proxy/array-content',
            method: 'POST',
            headers: ['content-type' => 'application/json']
        );
        $this->assertEquals(200, $response->getStatus());

        $headers = $response->getHeaders();
        $this->assertArrayHasKey('authorization', $headers);
        $this->assertEquals('Bearer eyJhbGciOiJSUzI1NiJ9', $headers['authorization'][0]);

        $content = $response->getBody()->buffer();
        $content = json_decode($content, true);

        $this->assertEquals('POST', $content['method']);
        $this->assertEquals([], $content['get']);

        $this->assertCount(6, $content['post']);

        $expected = ['id' => 3, 'name' => 'name 3'];
        $this->assertEquals($expected, $content['post'][2]);

        $this->assertArrayHasKey('content-type', $content['headers']);
        $this->assertEquals('application/json', $content['headers']['content-type'][0]);
    }

    public function testStringContent(): void
    {
        $response = $this->HttpClient->request(
            uri: 'http://127.0.0.1/response/proxy/string-content',
            method: 'POST',
            headers: ['content-type' => 'application/json']
        );
        $this->assertEquals(200, $response->getStatus());

        $headers = $response->getHeaders();
        $this->assertArrayHasKey('authorization', $headers);
        $this->assertEquals('Bearer eyJhbGciOiJSUzI1NiJ9', $headers['authorization'][0]);

        $content = $response->getBody()->buffer();
        $content = json_decode($content, true);

        $this->assertEquals('POST', $content['method']);
        $this->assertEquals([], $content['get']);

        $this->assertEquals(['id' => 100], $content['post']);

        $this->assertArrayHasKey('content-type', $content['headers']);
        $this->assertEquals('application/json', $content['headers']['content-type'][0]);
    }
}