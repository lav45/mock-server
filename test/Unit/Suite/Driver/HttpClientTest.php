<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Driver;

use Amp\Cancellation;
use Amp\Http\Client\DelegateHttpClient;
use Amp\Http\Client\Request;
use Amp\Http\Client\Response;
use Amp\NullCancellation;
use Lav45\MockServer\Driver\HttpClient;
use PHPUnit\Framework\TestCase;

use function Amp\ByteStream\buffer;

final class HttpClientTest extends TestCase
{
    private function createCapturingDelegate(): DelegateHttpClient
    {
        return new class implements DelegateHttpClient {
            public Request|null $capturedRequest = null;

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
        $delegate = $this->createCapturingDelegate();
        $original = new HttpClient($delegate);
        $original->request('https://example.com');

        $this->assertFalse($delegate->capturedRequest->hasAttribute('logLabel'));
    }

    public function testWithLabelSetsLogLabelOnRequest(): void
    {
        $delegate = $this->createCapturingDelegate();
        $client = new HttpClient($delegate);

        $client->withLabel('upstream')->request('https://example.com');

        $this->assertSame('upstream', $delegate->capturedRequest->getAttribute('logLabel'));
    }

    public function testWithoutLabelNoLogLabelAttribute(): void
    {
        $delegate = $this->createCapturingDelegate();
        $client = new HttpClient($delegate);

        $client->request('https://example.com');

        $this->assertFalse($delegate->capturedRequest->hasAttribute('logLabel'));
    }

    public function testBodyIsPassedToRequest(): void
    {
        $delegate = $this->createCapturingDelegate();
        $client = new HttpClient($delegate);

        $client->request('https://example.com', body: 'hello');

        $this->assertSame('hello', buffer($delegate->capturedRequest->getBody()->getContent()));
    }

    public function testHeadersArePassedToRequest(): void
    {
        $delegate = $this->createCapturingDelegate();
        $client = new HttpClient($delegate);

        $client->request('https://example.com', headers: ['X-Token' => 'secret']);

        $this->assertSame('secret', $delegate->capturedRequest->getHeader('X-Token'));
    }
}
