<?php declare(strict_types=1);

namespace lav45\MockServer\test\suite\Mock\Response;

use lav45\MockServer\HttpClient;
use PHPUnit\Framework\TestCase;

/**
 * @see \lav45\MockServer\Mock\Response\Content
 */
class ContentTest extends TestCase
{
    private HttpClient $HttpClient;

    protected function setUp(): void
    {
        $this->HttpClient = (new HttpClient())->build();
    }

    public function testJson(): void
    {
        $response = $this->HttpClient->request('http://127.0.0.1/response/content/json');
        $this->assertEquals(200, $response->getStatus());

        $headers = $response->getHeaders();
        $this->assertArrayHasKey('content-type', $headers);
        $this->assertEquals('application/json', $headers['content-type'][0]);

        $content = $response->getBody()->buffer();
        $this->assertEquals(['OK'], json_decode($content, true));
    }

    public function testText(): void
    {
        $response = $this->HttpClient->request('http://127.0.0.1/response/content/text');
        $this->assertEquals(200, $response->getStatus());

        $headers = $response->getHeaders();
        $this->assertArrayHasKey('content-type', $headers);
        $this->assertEquals('text/plain; charset=utf-8', $headers['content-type'][0]);

        $this->assertEquals('OK', $response->getBody()->buffer());
    }

    public function testHeaders(): void
    {
        $response = $this->HttpClient->request('http://127.0.0.1/response/content/headers');
        $this->assertEquals(200, $response->getStatus());

        $headers = $response->getHeaders();

        $this->assertArrayHasKey('x-type', $headers);
        $this->assertEquals('content', $headers['x-type'][0]);

        $this->assertArrayHasKey('x-id', $headers);
        $this->assertEquals('100', $headers['x-id'][0]);
    }

    public function testStatus(): void
    {
        $response = $this->HttpClient->request('http://127.0.0.1/response/content/status');
        $this->assertEquals(401, $response->getStatus());
        $this->assertEquals('Unauthorized', $response->getBody()->buffer());
    }
}