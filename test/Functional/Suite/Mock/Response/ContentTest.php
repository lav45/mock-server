<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Functional\Suite\Mock\Response;

use Lav45\MockServer\Infrastructure\Service\HttpClientFactory;
use Lav45\MockServer\Infrastructure\Service\HttpClientInterface;
use PHPUnit\Framework\TestCase;

class ContentTest extends TestCase
{
    private HttpClientInterface $HttpClient;

    protected function setUp(): void
    {
        $this->HttpClient = HttpClientFactory::create();
    }

    public function testJson(): void
    {
        $response = $this->HttpClient->request('http://127.0.0.1/response/content/json');
        $this->assertEquals(200, $response->getStatus());

        $headers = $response->getHeaders();
        $this->assertArrayHasKey('content-type', $headers);
        $this->assertEquals('application/json', $headers['content-type'][0]);

        $content = $response->getBody()->buffer();
        $content = \json_decode($content, true);
        $this->assertArrayHasKey('id', $content);

        $ids = \explode('/', $content['id']);
        $this->assertCount(2, $ids);

        $uuidPattern = '~^[\da-f]{8}-[\da-f]{4}-[\da-f]{4}-[\da-f]{4}-[\da-f]{12}$~';
        $this->assertMatchesRegularExpression($uuidPattern, $ids[0]);
        $this->assertMatchesRegularExpression($uuidPattern, $ids[1]);

        $this->assertArrayHasKey('term', $content);
        $this->assertEquals('xterm', $content['term']);
        $this->assertArrayHasKey('domain', $content);
        $this->assertEquals('test.server.com', $content['domain']);
        $this->assertArrayHasKey('url', $content);
        $this->assertEquals('https://test.server.com/v1', $content['url']);

        $response = $this->HttpClient->request('http://127.0.0.1/response/content/json');
        $this->assertEquals(200, $response->getStatus());

        $content = $response->getBody()->buffer();
        $content = \json_decode($content, true);

        $ids2 = \explode('/', $content['id']);
        $this->assertCount(2, $ids2);

        $this->assertNotEquals($ids[0], $ids2[0]);
        $this->assertNotEquals($ids[1], $ids2[1]);
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
