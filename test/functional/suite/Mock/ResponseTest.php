<?php declare(strict_types=1);

namespace lav45\MockServer\test\functional\suite\Mock;

use lav45\MockServer\HttpClient;
use PHPUnit\Framework\TestCase;

/**
 * @see \lav45\MockServer\Mock\Response
 */
class ResponseTest extends TestCase
{
    private HttpClient $HttpClient;

    protected function setUp(): void
    {
        $this->HttpClient = (new HttpClient())->build();
    }

    public function testIndex(): void
    {
        $response = $this->HttpClient->request('http://127.0.0.1');
        $this->assertEquals(404, $response->getStatus());
    }

    public function testNotFound(): void
    {
        $response = $this->HttpClient->request('http://127.0.0.1/response/not-found');
        $this->assertEquals(404, $response->getStatus());
    }

    public function testMethodNotAllowed(): void
    {
        $response = $this->HttpClient->request('http://127.0.0.1/response/delay', 'POST');
        $this->assertEquals(405, $response->getStatus());
    }

    public function testDelay(): void
    {
        $start = microtime(true);
        $response = $this->HttpClient->request('http://127.0.0.1/response/delay');
        $end = microtime(true);

        $this->assertEquals(200, $response->getStatus());
        $this->assertEquals(0.2, round($end - $start, 1));
    }
}