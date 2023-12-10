<?php declare(strict_types=1);

namespace lav45\MockServer\test\suite\Mock;

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
        $this->assertEquals(200, $response->getStatus());

        $content = $response->getBody()->buffer();
        $this->assertEquals('', $content);
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