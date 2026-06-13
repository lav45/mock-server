<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Responder;

use Amp\Http\Client\Request as HttpClientRequest;
use Amp\Http\Client\Response as HttpClientResponse;
use Lav45\MockServer\Domain\Direct;
use Lav45\MockServer\Domain\ValueObject\Body;
use Lav45\MockServer\Domain\ValueObject\HttpHeaders;
use Lav45\MockServer\Domain\ValueObject\HttpMethod;
use Lav45\MockServer\Domain\ValueObject\Url;
use Lav45\MockServer\Responder\DirectHandler;
use Lav45\MockServer\Responder\HttpClient;
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

    private function createHttpClientStub(int $status, string $body): HttpClient
    {
        return new readonly class ($status, $body) implements HttpClient {
            public function __construct(
                private int    $status,
                private string $body,
            ) {}

            public function request(
                string      $uri,
                string      $method = 'GET',
                array|null  $headers = null,
                string|null $body = null,
            ): HttpClientResponse {
                return new HttpClientResponse(
                    '1.1',
                    $this->status,
                    'OK',
                    [],
                    $this->body,
                    new HttpClientRequest($uri),
                );
            }
        };
    }

    private function createCapturingHttpClient(): HttpClient
    {
        return new class implements HttpClient {
            public array $calls = [];

            public function request(
                string      $uri,
                string      $method = 'GET',
                array|null  $headers = null,
                string|null $body = null,
            ): HttpClientResponse {
                $this->calls[] = [
                    'uri' => $uri,
                    'method' => $method,
                    'headers' => $headers,
                    'body' => $body,
                ];
                return new HttpClientResponse('1.1', 200, 'OK', [], '{}', new HttpClientRequest($uri));
            }
        };
    }

    // --- Request forwarding ---

    public function testForwardsUrlToHttpClient(): void
    {
        $httpClient = $this->createCapturingHttpClient();
        $handler = new DirectHandler($httpClient);

        $handler->request($this->createDirect(url: 'https://remote.example.com/api'));

        $this->assertSame('https://remote.example.com/api', $httpClient->calls[0]['uri']);
    }

    public function testForwardsMethodToHttpClient(): void
    {
        $httpClient = $this->createCapturingHttpClient();
        $handler = new DirectHandler($httpClient);

        $handler->request($this->createDirect(method: 'PUT'));

        $this->assertSame('PUT', $httpClient->calls[0]['method']);
    }

    public function testForwardsBodyToHttpClient(): void
    {
        $httpClient = $this->createCapturingHttpClient();
        $handler = new DirectHandler($httpClient);

        $handler->request($this->createDirect(body: '{"key":"value"}'));

        $this->assertSame('{"key":"value"}', $httpClient->calls[0]['body']);
    }

    public function testForwardsHeadersToHttpClient(): void
    {
        $httpClient = $this->createCapturingHttpClient();
        $handler = new DirectHandler($httpClient);

        $handler->request($this->createDirect(headers: ['X-Token' => 'secret']));

        $this->assertSame('secret', $httpClient->calls[0]['headers']['X-Token']);
    }

    // --- Response handling ---

    public function testInjectsRemoteResponse(): void
    {
        $httpClient = $this->createHttpClientStub(200, '{"response":{"status":201}}');
        $handler = new DirectHandler($httpClient);

        $result = $handler->request($this->createDirect())->replace([]);

        $this->assertSame(['status' => 201], $result['response']);
    }

    public function testUnescapesBracesInRemoteData(): void
    {
        $body = \json_encode(['response' => ['body' => '\{\{x\}\}']], JSON_THROW_ON_ERROR);
        $handler = new DirectHandler($this->createHttpClientStub(200, $body));

        $result = $handler->request($this->createDirect())->replace([]);

        $this->assertSame('{{x}}', $result['response']['body']);
    }

    public function testEmptyJsonObjectInjectsNothing(): void
    {
        $httpClient = $this->createHttpClientStub(200, '{}');
        $handler = new DirectHandler($httpClient);

        $result = $handler->request($this->createDirect())->replace(['response' => ['status' => 200]]);

        $this->assertSame(['status' => 200], $result['response']);
    }

    public function testThrowsRuntimeExceptionOnNonSuccessfulStatus(): void
    {
        $httpClient = $this->createHttpClientStub(404, 'not found');
        $handler = new DirectHandler($httpClient);

        $this->expectException(\RuntimeException::class);
        $handler->request($this->createDirect());
    }

    public function testExceptionCodeMatchesUpstreamStatus(): void
    {
        $httpClient = $this->createHttpClientStub(503, 'service unavailable');
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
        $httpClient = $this->createHttpClientStub(422, 'validation error');
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
        $httpClient = $this->createHttpClientStub(200, 'plain text response');
        $handler = new DirectHandler($httpClient);

        $this->expectException(\RuntimeException::class);
        $handler->request($this->createDirect());
    }
}
