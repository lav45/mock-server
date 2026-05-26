<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Responder\HttpClient\Interceptor;

use Amp\Cancellation;
use Amp\Http\Client\DelegateHttpClient;
use Amp\Http\Client\Request;
use Amp\Http\Client\Response;
use Amp\NullCancellation;
use Lav45\MockServer\Responder\HttpClient\Interceptor\Logger;
use Lav45\MockServer\Test\Unit\Components\FakeLogger;
use PHPUnit\Framework\TestCase;

final class LoggerTest extends TestCase
{
    private function createDelegateStub(int $status, string $body = ''): DelegateHttpClient
    {
        return new class ($status, $body) implements DelegateHttpClient {
            public function __construct(
                private readonly int    $status,
                private readonly string $body,
            ) {}

            public function request(Request $request, Cancellation $cancellation): Response
            {
                return new Response('1.1', $this->status, 'OK', [], $this->body, $request);
            }
        };
    }

    public function testLogsInfoOnSuccessfulResponse(): void
    {
        $fakeLogger = new FakeLogger();
        $interceptor = new Logger($fakeLogger);
        $request = new Request('https://example.com', 'GET');
        $cancellation = new NullCancellation();
        $delegate = $this->createDelegateStub(200);

        $interceptor->request($request, $cancellation, $delegate);

        $this->assertCount(1, $fakeLogger->getMessages('info'));
        $this->assertCount(0, $fakeLogger->getMessages('warning'));
    }

    public function testLogsWarningOnNonSuccessfulResponse(): void
    {
        $fakeLogger = new FakeLogger();
        $interceptor = new Logger($fakeLogger);
        $request = new Request('https://example.com', 'GET');
        $cancellation = new NullCancellation();
        $delegate = $this->createDelegateStub(500);

        $interceptor->request($request, $cancellation, $delegate);

        $this->assertCount(1, $fakeLogger->getMessages('warning'));
        $this->assertCount(0, $fakeLogger->getMessages('info'));
    }

    public function testLogsWarningOn4xx(): void
    {
        $fakeLogger = new FakeLogger();
        $interceptor = new Logger($fakeLogger);
        $request = new Request('https://example.com', 'GET');
        $cancellation = new NullCancellation();
        $delegate = $this->createDelegateStub(404);

        $interceptor->request($request, $cancellation, $delegate);

        $this->assertCount(1, $fakeLogger->getMessages('warning'));
    }

    public function testMessageContainsStatusMethodAndUri(): void
    {
        $fakeLogger = new FakeLogger();
        $interceptor = new Logger($fakeLogger);
        $request = new Request('https://example.com/path', 'POST');
        $cancellation = new NullCancellation();
        $delegate = $this->createDelegateStub(201);

        $interceptor->request($request, $cancellation, $delegate);

        $messages = $fakeLogger->getMessages('info');
        $this->assertSame('201 POST https://example.com/path', $messages[0]);
    }

    public function testMessageIncludesLogLabelPrefix(): void
    {
        $fakeLogger = new FakeLogger();
        $interceptor = new Logger($fakeLogger);
        $request = new Request('https://example.com', 'GET');
        $request->setAttribute('logLabel', 'upstream');
        $cancellation = new NullCancellation();
        $delegate = $this->createDelegateStub(200);

        $interceptor->request($request, $cancellation, $delegate);

        $messages = $fakeLogger->getMessages('info');
        $this->assertSame('upstream: 200 GET https://example.com', $messages[0]);
    }

    public function testNoLabelPrefixWhenAttributeAbsent(): void
    {
        $fakeLogger = new FakeLogger();
        $interceptor = new Logger($fakeLogger);
        $request = new Request('https://example.com', 'DELETE');
        $cancellation = new NullCancellation();
        $delegate = $this->createDelegateStub(204);

        $interceptor->request($request, $cancellation, $delegate);

        $messages = $fakeLogger->getMessages('info');
        $this->assertSame('204 DELETE https://example.com', $messages[0]);
    }

    public function testReturnsResponseFromDelegate(): void
    {
        $interceptor = new Logger();
        $request = new Request('https://example.com', 'GET');
        $cancellation = new NullCancellation();
        $delegate = $this->createDelegateStub(202, 'accepted');

        $response = $interceptor->request($request, $cancellation, $delegate);

        $this->assertSame(202, $response->getStatus());
    }
}
