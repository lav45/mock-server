<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Functional\Suite\Mock;

use Lav45\MockServer\Infrastructure\HttpClient\Factory as HttpClientFactory;
use Lav45\MockServer\Infrastructure\HttpClient\HttpClientInterface;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    private HttpClientInterface $HttpClient;

    protected function setUp(): void
    {
        $this->HttpClient = HttpClientFactory::create();
    }

    public function testIndex(): void
    {
        $response = $this->HttpClient->request(MOCK_SERVER_URL);
        $this->assertEquals(404, $response->getStatus());
    }

    public function testNotFound(): void
    {
        $response = $this->HttpClient->request(MOCK_SERVER_URL . '/response/not-found');
        $this->assertEquals(404, $response->getStatus());
    }

    public function testMethodNotAllowed(): void
    {
        $response = $this->HttpClient->request(MOCK_SERVER_URL . '/response/delay', 'POST');
        $this->assertEquals(405, $response->getStatus());
    }

    public function testDelay(): void
    {
        $start = \microtime(true);
        $response = $this->HttpClient->request(MOCK_SERVER_URL . '/response/delay');
        $end = \microtime(true);

        $this->assertEquals(200, $response->getStatus());
        $this->assertEquals(0.2, \round($end - $start, 1));
    }
}
