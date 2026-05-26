<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Middleware;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Lav45\MockServer\Middleware\ResponseTypeMiddleware;
use Lav45\MockServer\Test\Unit\Components\FakeHttpDriverClient;
use League\Uri\Http;
use PHPUnit\Framework\TestCase;

final class ResponseTypeMiddlewareTest extends TestCase
{
    private function createRequest(array $data = []): Request
    {
        $request = new Request(new FakeHttpDriverClient(), 'GET', Http::new('https://localhost/'));
        $request->setAttribute('data', $data);
        return $request;
    }

    private function nextCapturing(string|null &$capturedType): \Closure
    {
        return static function (Request $request) use (&$capturedType): Response {
            $capturedType = $request->getAttribute('responseType');
            return new Response(200);
        };
    }

    public function testAlwaysCallsNext(): void
    {
        $called = false;
        $next = function () use (&$called): Response {
            $called = true;
            return new Response(200);
        };

        $middleware = new ResponseTypeMiddleware('content');
        $middleware($this->createRequest(), $next);

        $this->assertTrue($called);
    }

    public function testUsesTypeFromResponseData(): void
    {
        $capturedType = null;
        $middleware = new ResponseTypeMiddleware('content');
        $middleware(
            $this->createRequest(['response' => ['type' => 'proxy']]),
            $this->nextCapturing($capturedType),
        );

        $this->assertSame('proxy', $capturedType);
    }

    public function testNormalizesTypeToLowercase(): void
    {
        $capturedType = null;
        $middleware = new ResponseTypeMiddleware('content');
        $middleware(
            $this->createRequest(['response' => ['type' => 'CONTENT']]),
            $this->nextCapturing($capturedType),
        );

        $this->assertSame('content', $capturedType);
    }

    public function testFallsBackToDefaultWhenResponseTypeNotSet(): void
    {
        $capturedType = null;
        $middleware = new ResponseTypeMiddleware('data');
        $middleware(
            $this->createRequest(['response' => ['text' => 'ok']]),
            $this->nextCapturing($capturedType),
        );

        $this->assertSame('data', $capturedType);
    }

    public function testFallsBackToDefaultWhenResponseKeyMissing(): void
    {
        $capturedType = null;
        $middleware = new ResponseTypeMiddleware('content');
        $middleware(
            $this->createRequest([]),
            $this->nextCapturing($capturedType),
        );

        $this->assertSame('content', $capturedType);
    }
}
