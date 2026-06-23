<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Middleware;

use Lav45\MockServer\DataFactory\DataBuilder;
use Lav45\MockServer\Engine\Http\ServerRequest;
use Lav45\MockServer\Engine\Http\ServerResponse;
use Lav45\MockServer\Middleware\MiddlewareHandler;
use Lav45\MockServer\Middleware\ThrottlingMiddleware;
use Lav45\MockServer\Parser\InlineParser;
use Lav45\MockServer\Parser\ParamParser;
use Lav45\MockServer\Test\Unit\Components\CallableHandler;
use Lav45\MockServer\Test\Unit\Components\FakeServerRequest;
use PHPUnit\Framework\TestCase;
use Revolt\EventLoop;

use function Amp\async;

final class ThrottlingMiddlewareTest extends TestCase
{
    private function createRequest(array $data = []): ServerRequest
    {
        $parser = new ParamParser(new class implements InlineParser {
            public function replace(mixed $data): mixed
            {
                return $data;
            }
        });
        $request = new FakeServerRequest('GET', 'https://localhost/');
        $request->setAttribute('data', $data);
        $request->setAttribute('parser', $parser);
        return $request;
    }

    private function nextReturning(int $status): MiddlewareHandler
    {
        return new CallableHandler(static fn(ServerRequest $r): ServerResponse => new ServerResponse($status));
    }

    // --- Passthrough (synchronous) ---

    public function testPassesThroughWhenDelayKeyMissing(): void
    {
        $request = $this->createRequest(['response' => ['text' => 'ok']]);

        $middleware = new ThrottlingMiddleware(new DataBuilder());
        $response = $middleware->process($request, $this->nextReturning(200));

        $this->assertSame(200, $response->getStatus());
    }

    public function testPassesThroughWhenDelayIsZero(): void
    {
        $request = $this->createRequest(['response' => ['delay' => 0.0]]);

        $middleware = new ThrottlingMiddleware(new DataBuilder());
        $response = $middleware->process($request, $this->nextReturning(200));

        $this->assertSame(200, $response->getStatus());
    }

    public function testPassesThroughWhenResponseKeyMissing(): void
    {
        $request = $this->createRequest([]);

        $middleware = new ThrottlingMiddleware(new DataBuilder());
        $response = $middleware->process($request, $this->nextReturning(200));

        $this->assertSame(200, $response->getStatus());
    }

    public function testAlwaysCallsNext(): void
    {
        $called = false;
        $next = function () use (&$called): ServerResponse {
            $called = true;
            return new ServerResponse(200);
        };

        $request = $this->createRequest(['response' => ['text' => 'ok']]);
        $middleware = new ThrottlingMiddleware(new DataBuilder());
        $middleware->process($request, new CallableHandler($next));

        $this->assertTrue($called);
    }

    // --- With delay (runs inside fiber) ---

    public function testReturnsResponseWhenDelayIsPositive(): void
    {
        $request = $this->createRequest(['response' => ['delay' => 0.001]]);

        $capturedResponse = null;
        async(function () use ($request, &$capturedResponse): void {
            $middleware = new ThrottlingMiddleware(new DataBuilder());
            $capturedResponse = $middleware->process($request, $this->nextReturning(201));
        });
        EventLoop::run();

        $this->assertSame(201, $capturedResponse->getStatus());
    }

    public function testElapsedTimeReducesActualDelay(): void
    {
        // If $next takes longer than the delay, the sleep must NOT be called
        // (timeout <= 0.0 branch). We verify no error is thrown and response is returned.
        $slowNext = static function (): ServerResponse {
            \usleep(5_000); // 5 ms — longer than 0.001 s delay
            return new ServerResponse(200);
        };

        $request = $this->createRequest(['response' => ['delay' => 0.001]]);

        $capturedResponse = null;
        async(function () use ($request, $slowNext, &$capturedResponse): void {
            $middleware = new ThrottlingMiddleware(new DataBuilder());
            $capturedResponse = $middleware->process($request, new CallableHandler($slowNext));
        });
        EventLoop::run();

        $this->assertSame(200, $capturedResponse->getStatus());
    }
}
