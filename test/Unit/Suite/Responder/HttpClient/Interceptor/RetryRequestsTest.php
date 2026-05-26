<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Responder\HttpClient\Interceptor;

use Amp\Cancellation;
use Amp\Http\Client\DelegateHttpClient;
use Amp\Http\Client\Request;
use Amp\Http\Client\Response;
use Amp\Http\Client\SocketException;
use Amp\NullCancellation;
use Lav45\MockServer\Responder\HttpClient\Interceptor\RetryRequests;
use PHPUnit\Framework\TestCase;

final class RetryRequestsTest extends TestCase
{
    private function createSuccessDelegate(): DelegateHttpClient
    {
        return new class implements DelegateHttpClient {
            public int $callCount = 0;

            public function request(Request $request, Cancellation $cancellation): Response
            {
                $this->callCount++;
                return new Response('1.1', 200, 'OK', [], '', $request);
            }
        };
    }

    private function createFailingDelegate(int $failTimes): DelegateHttpClient
    {
        return new class ($failTimes) implements DelegateHttpClient {
            public int $callCount = 0;

            public function __construct(private int $failTimes) {}

            public function request(Request $request, Cancellation $cancellation): Response
            {
                $this->callCount++;
                if ($this->failTimes > 0) {
                    $this->failTimes--;
                    throw new SocketException('connection error');
                }
                return new Response('1.1', 200, 'OK', [], '', $request);
            }
        };
    }

    private function createAlwaysFailingDelegate(): DelegateHttpClient
    {
        return new class implements DelegateHttpClient {
            public int $callCount = 0;

            public function request(Request $request, Cancellation $cancellation): Response
            {
                $this->callCount++;
                throw new SocketException('connection error');
            }
        };
    }

    public function testReturnsResponseOnFirstSuccessfulAttempt(): void
    {
        $delegate = $this->createSuccessDelegate();
        $interceptor = new RetryRequests(retryLimit: 3);
        $request = new Request('https://example.com', 'GET');
        $cancellation = new NullCancellation();

        $response = $interceptor->request($request, $cancellation, $delegate);

        $this->assertSame(200, $response->getStatus());
        $this->assertSame(1, $delegate->callCount);
    }

    public function testRetriesOnSocketExceptionForIdempotentRequest(): void
    {
        $delegate = $this->createFailingDelegate(failTimes: 1);
        $interceptor = new RetryRequests(retryLimit: 3);
        $request = new Request('https://example.com', 'GET');
        $cancellation = new NullCancellation();

        $response = $interceptor->request($request, $cancellation, $delegate);

        $this->assertSame(200, $response->getStatus());
        $this->assertSame(2, $delegate->callCount);
    }

    public function testThrowsAfterExceedingRetryLimit(): void
    {
        $delegate = $this->createAlwaysFailingDelegate();
        $interceptor = new RetryRequests(retryLimit: 2);
        $request = new Request('https://example.com', 'GET');
        $cancellation = new NullCancellation();

        $this->expectException(SocketException::class);
        $interceptor->request($request, $cancellation, $delegate);
    }

    public function testTotalAttemptsEqualsRetryLimitPlusOne(): void
    {
        $delegate = $this->createAlwaysFailingDelegate();
        $interceptor = new RetryRequests(retryLimit: 3);
        $request = new Request('https://example.com', 'GET');
        $cancellation = new NullCancellation();

        try {
            $interceptor->request($request, $cancellation, $delegate);
        } catch (SocketException) {
            // expected
        }

        $this->assertSame(4, $delegate->callCount);
    }

    public function testRetriesUntilSuccessWithinLimit(): void
    {
        $delegate = $this->createFailingDelegate(failTimes: 2);
        $interceptor = new RetryRequests(retryLimit: 3);
        $request = new Request('https://example.com', 'PUT');
        $cancellation = new NullCancellation();

        $response = $interceptor->request($request, $cancellation, $delegate);

        $this->assertSame(200, $response->getStatus());
        $this->assertSame(3, $delegate->callCount);
    }
}
