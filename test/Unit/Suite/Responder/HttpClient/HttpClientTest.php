<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Responder\HttpClient;

use Amp\Cancellation;
use Amp\Http\Client\DelegateHttpClient;
use Amp\Http\Client\Request;
use Amp\Http\Client\Response;
use Amp\NullCancellation;
use Lav45\MockServer\Responder\HttpClient\HttpClient;
use PHPUnit\Framework\TestCase;

use function Amp\ByteStream\buffer;

final class HttpClientTest extends TestCase
{
    private function createCapturingDelegate(Request|null &$capturedRequest = null): DelegateHttpClient
    {
        return new class ($capturedRequest) implements DelegateHttpClient {
            public function __construct(private mixed &$capturedRequest) {}

            public function request(Request $request, Cancellation $cancellation = new NullCancellation()): Response
            {
                $this->capturedRequest = $request;
                return new Response('1.1', 200, 'OK', [], '', $request);
            }
        };
    }

    public function testWithLabelReturnsNewInstance(): void
    {
        $original = new HttpClient($this->createCapturingDelegate());

        $labeled = $original->withLabel('my-label');

        $this->assertNotSame($original, $labeled);
    }

    public function testOriginalUnchangedAfterWithLabel(): void
    {
        $capturedRequest = null;
        $delegate = $this->createCapturingDelegate($capturedRequest);
        $original = new HttpClient($delegate);
        $original->request('https://example.com');

        $this->assertFalse($capturedRequest->hasAttribute('logLabel'));
    }

    public function testWithLabelSetsLogLabelOnRequest(): void
    {
        $capturedRequest = null;
        $delegate = $this->createCapturingDelegate($capturedRequest);
        $client = new HttpClient($delegate);

        $client->withLabel('upstream')->request('https://example.com');

        $this->assertSame('upstream', $capturedRequest->getAttribute('logLabel'));
    }

    public function testWithoutLabelNoLogLabelAttribute(): void
    {
        $capturedRequest = null;
        $delegate = $this->createCapturingDelegate($capturedRequest);
        $client = new HttpClient($delegate);

        $client->request('https://example.com');

        $this->assertFalse($capturedRequest->hasAttribute('logLabel'));
    }

    public function testBodyIsPassedToRequest(): void
    {
        $capturedRequest = null;
        $delegate = $this->createCapturingDelegate($capturedRequest);
        $client = new HttpClient($delegate);

        $client->request('https://example.com', body: 'hello');

        $this->assertSame('hello', buffer($capturedRequest->getBody()->getContent()));
    }

    public function testHeadersArePassedToRequest(): void
    {
        $capturedRequest = null;
        $delegate = $this->createCapturingDelegate($capturedRequest);
        $client = new HttpClient($delegate);

        $client->request('https://example.com', headers: ['X-Token' => 'secret']);

        $this->assertSame('secret', $capturedRequest->getHeader('X-Token'));
    }
}
