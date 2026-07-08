<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Functional\Suite;

use Lav45\MockServer\Driver\HttpClientFactory;
use Lav45\MockServer\Engine\HttpClient;
use PHPUnit\Framework\TestCase;

final class TlsTest extends TestCase
{
    private HttpClient $HttpClient;

    protected function setUp(): void
    {
        $this->HttpClient = new HttpClientFactory()->create();
    }

    public function testIndexOverTls(): void
    {
        $response = $this->HttpClient->request(MOCK_SERVER_TLS_URL);
        $this->assertEquals(200, $response->getStatus());
        $this->assertEquals('', $response->getBody()->stream->read());
    }

    public function testContentOverTls(): void
    {
        $response = $this->HttpClient->request(MOCK_SERVER_TLS_URL . '/response/content/text');
        $this->assertEquals(200, $response->getStatus());
        $this->assertEquals('OK', $response->getBody()->stream->read());
    }

    public function testNotFoundOverTls(): void
    {
        $response = $this->HttpClient->request(MOCK_SERVER_TLS_URL . '/response/not-found');
        $this->assertEquals(404, $response->getStatus());
    }
}
