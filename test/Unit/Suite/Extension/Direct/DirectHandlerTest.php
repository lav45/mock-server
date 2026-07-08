<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Extension\Direct;

use Lav45\MockServer\Domain\Direct;
use Lav45\MockServer\Domain\ValueObject\Body;
use Lav45\MockServer\Domain\ValueObject\HttpHeaders;
use Lav45\MockServer\Domain\ValueObject\HttpMethod;
use Lav45\MockServer\Domain\ValueObject\Url;
use Lav45\MockServer\Extension\Direct\DirectHandler;
use Lav45\MockServer\Test\Unit\Components\FakeHttpClient;
use PHPUnit\Framework\TestCase;

final class DirectHandlerTest extends TestCase
{
    private function createDirect(
        string $url = 'https://remote.example.com',
        string $method = 'POST',
        array  $headers = [],
        string $body = '',
    ): Direct {
        return new Direct(
            url: new Url($url),
            method: new HttpMethod($method),
            headers: HttpHeaders::fromArray($headers),
            body: Body::new($body),
        );
    }

    // --- Request forwarding ---

    public function testForwardsUrlToHttpClient(): void
    {
        $httpClient = new FakeHttpClient(body: '{}');
        $handler = new DirectHandler($httpClient);

        $handler->request($this->createDirect(url: 'https://remote.example.com/api'));

        $this->assertSame('https://remote.example.com/api', $httpClient->calls[0]['uri']);
    }

    public function testForwardsMethodToHttpClient(): void
    {
        $httpClient = new FakeHttpClient(body: '{}');
        $handler = new DirectHandler($httpClient);

        $handler->request($this->createDirect(method: 'PUT'));

        $this->assertSame('PUT', $httpClient->calls[0]['method']);
    }

    public function testForwardsBodyToHttpClient(): void
    {
        $httpClient = new FakeHttpClient(body: '{}');
        $handler = new DirectHandler($httpClient);

        $handler->request($this->createDirect(body: '{"key":"value"}'));

        $this->assertSame('{"key":"value"}', $httpClient->calls[0]['body']);
    }

    public function testForwardsHeadersToHttpClient(): void
    {
        $httpClient = new FakeHttpClient(body: '{}');
        $handler = new DirectHandler($httpClient);

        $handler->request($this->createDirect(headers: ['X-Token' => 'secret']));

        $this->assertSame('secret', $httpClient->calls[0]['headers']['X-Token']);
    }

    // --- Response handling ---

    public function testInjectsRemoteResponse(): void
    {
        $httpClient = new FakeHttpClient(status: 200, body: '{"response":{"status":201}}');
        $handler = new DirectHandler($httpClient);

        $result = $handler->request($this->createDirect())->replace([]);

        $this->assertSame(['status' => 201], $result['response']);
    }

    public function testUnescapesBracesInRemoteData(): void
    {
        $body = \json_encode(['response' => ['body' => '\{\{x\}\}']], JSON_THROW_ON_ERROR);
        $handler = new DirectHandler(new FakeHttpClient(status: 200, body: $body));

        $result = $handler->request($this->createDirect())->replace([]);

        $this->assertSame('{{x}}', $result['response']['body']);
    }

    public function testEmptyJsonObjectInjectsNothing(): void
    {
        $httpClient = new FakeHttpClient(status: 200, body: '{}');
        $handler = new DirectHandler($httpClient);

        $result = $handler->request($this->createDirect())->replace(['response' => ['status' => 200]]);

        $this->assertSame(['status' => 200], $result['response']);
    }

    public function testThrowsRuntimeExceptionOnNonSuccessfulStatus(): void
    {
        $httpClient = new FakeHttpClient(status: 404, body: 'not found');
        $handler = new DirectHandler($httpClient);

        $this->expectException(\RuntimeException::class);
        $handler->request($this->createDirect());
    }

    public function testExceptionCodeMatchesUpstreamStatus(): void
    {
        $httpClient = new FakeHttpClient(status: 503, body: 'service unavailable');
        $handler = new DirectHandler($httpClient);

        try {
            $handler->request($this->createDirect());
            $this->fail('Expected RuntimeException');
        } catch (\RuntimeException $e) {
            $this->assertSame(503, $e->getCode());
        }
    }

    public function testExceptionMessageContainsResponseBody(): void
    {
        $httpClient = new FakeHttpClient(status: 422, body: 'validation error');
        $handler = new DirectHandler($httpClient);

        try {
            $handler->request($this->createDirect());
            $this->fail('Expected RuntimeException');
        } catch (\RuntimeException $e) {
            $this->assertSame('validation error', $e->getMessage());
        }
    }

    public function testThrowsWhenSuccessfulStatusButBodyIsNotJson(): void
    {
        $httpClient = new FakeHttpClient(status: 200, body: 'plain text response');
        $handler = new DirectHandler($httpClient);

        $this->expectException(\RuntimeException::class);
        $handler->request($this->createDirect());
    }

    public function testThrowsForStatus300(): void
    {
        $httpClient = new FakeHttpClient(status: 300, body: '{}');
        $handler = new DirectHandler($httpClient);

        $this->expectException(\RuntimeException::class);
        $handler->request($this->createDirect());
    }

    public function testThrowsForStatus1xx(): void
    {
        $httpClient = new FakeHttpClient(status: 150, body: '{}');
        $handler = new DirectHandler($httpClient);

        $this->expectException(\RuntimeException::class);
        $handler->request($this->createDirect());
    }
}
