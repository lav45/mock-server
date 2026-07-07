<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Functional\Suite;

use Lav45\MockServer\Driver\HttpClientFactory;
use Lav45\MockServer\Engine\HttpClient;
use PHPUnit\Framework\TestCase;

class CorsTest extends TestCase
{
    private HttpClient $HttpClient;

    protected function setUp(): void
    {
        $this->HttpClient = new HttpClientFactory()->create();
    }

    public function testSimpleRequestAddsCorsHeaders(): void
    {
        $response = $this->HttpClient->request(
            uri: MOCK_SERVER_URL . '/cors',
            headers: ['origin' => 'https://example.com'],
        );

        $this->assertEquals(200, $response->getStatus());
        $this->assertEquals('OK', $response->getBody()->stream->read());

        $headers = $response->getHeaders();
        $this->assertEquals('*', $headers['access-control-allow-origin'][0]);

        $exposeHeaders = $headers['access-control-expose-headers'][0];
        $this->assertStringContainsString('x-total', $exposeHeaders);
        $this->assertStringNotContainsString('access-control-allow-origin', $exposeHeaders);
    }

    public function testPreflightRequest(): void
    {
        $response = $this->HttpClient->request(
            uri: MOCK_SERVER_URL . '/cors',
            method: 'OPTIONS',
            headers: [
                'origin' => 'https://example.com',
                'access-control-request-method' => 'DELETE',
                'access-control-request-headers' => 'X-Custom',
            ],
        );

        $this->assertEquals(204, $response->getStatus());

        $headers = $response->getHeaders();
        $this->assertEquals('*', $headers['access-control-allow-origin'][0]);
        $this->assertEquals('DELETE', $headers['access-control-allow-methods'][0]);
        $this->assertEquals('X-Custom', $headers['access-control-allow-headers'][0]);
        $this->assertEquals('86400', $headers['access-control-max-age'][0]);
    }

    public function testCorsAppliedToNotFound(): void
    {
        $response = $this->HttpClient->request(
            uri: MOCK_SERVER_URL . '/no-such-route',
            headers: ['origin' => 'https://example.com'],
        );

        $this->assertEquals(404, $response->getStatus());
        $this->assertEquals('*', $response->getHeaders()['access-control-allow-origin'][0]);
    }
}
