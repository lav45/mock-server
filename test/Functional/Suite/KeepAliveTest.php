<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Functional\Suite;

use Lav45\MockServer\Driver\HttpClientFactory;
use Lav45\MockServer\Engine\HttpClient;
use PHPUnit\Framework\TestCase;

class KeepAliveTest extends TestCase
{
    private HttpClient $httpClient;

    protected function setUp(): void
    {
        $this->httpClient = new HttpClientFactory(retryLimit: 0)->create();
    }

    public function testSecondRequestSucceedsWhenTheMockIgnoresTheBody(): void
    {
        $first = $this->httpClient->request(
            uri: MOCK_SERVER_URL . '/keep-alive/ignores-body',
            method: 'POST',
            headers: ['content-type' => 'application/json'],
            body: '{"name":"first"}',
        );

        self::assertSame(200, $first->getStatus());
        self::assertSame('OK', $first->getBody()->stream->read());

        $second = $this->httpClient->request(
            uri: MOCK_SERVER_URL . '/keep-alive/ignores-body',
            method: 'POST',
            headers: ['content-type' => 'application/json'],
            body: '{"name":"second"}',
        );

        self::assertSame(200, $second->getStatus());
        self::assertSame('OK', $second->getBody()->stream->read());
    }

    public function testBodyStillReachesAMockThatReadsIt(): void
    {
        $response = $this->httpClient->request(
            uri: MOCK_SERVER_URL . '/keep-alive/reads-body',
            method: 'POST',
            headers: ['content-type' => 'application/json'],
            body: '{"name":"result"}',
        );

        self::assertSame(200, $response->getStatus());
        self::assertSame('result', $response->getBody()->stream->read());
    }

    public function testThirdRequestStillWorksAfterAMockThatReadsTheBody(): void
    {
        $first = $this->httpClient->request(
            uri: MOCK_SERVER_URL . '/keep-alive/reads-body',
            method: 'POST',
            headers: ['content-type' => 'application/json'],
            body: '{"name":"result"}',
        );
        $first->getBody()->stream->read();

        $next = $this->httpClient->request(
            uri: MOCK_SERVER_URL . '/keep-alive/ignores-body',
            method: 'POST',
            headers: ['content-type' => 'application/json'],
            body: '{"name":"next"}',
        );

        self::assertSame(200, $next->getStatus());
    }
}
