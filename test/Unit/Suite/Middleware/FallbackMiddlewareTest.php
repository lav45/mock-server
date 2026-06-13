<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Middleware;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Lav45\MockServer\Middleware\FallbackMiddleware;
use Lav45\MockServer\Middleware\MiddlewareHandler;
use Lav45\MockServer\Test\Unit\Components\CallableHandler;
use Lav45\MockServer\Test\Unit\Components\FakeHttpDriverClient;
use Lav45\MockServer\Test\Unit\Components\FakeLogger;
use League\Uri\Http;
use PHPUnit\Framework\TestCase;

final class FallbackMiddlewareTest extends TestCase
{
    private function createRequest(array $data = []): Request
    {
        $request = new Request(new FakeHttpDriverClient(), 'GET', Http::new('https://localhost/'));
        $request->setAttribute('data', $data);
        return $request;
    }

    private function next(): MiddlewareHandler
    {
        return new CallableHandler(static fn(Request $request): Response => new Response(500));
    }

    public function testReturns404(): void
    {
        $middleware = new FallbackMiddleware();
        $response = $middleware->process($this->createRequest(), $this->next());

        $this->assertSame(404, $response->getStatus());
    }

    public function testLogsErrorWithSerializedData(): void
    {
        $logger = new FakeLogger();
        $middleware = new FallbackMiddleware($logger);
        $middleware->process($this->createRequest(['response' => ['text' => 'hi']]), $this->next());

        $errors = $logger->getMessages('error');
        $this->assertCount(1, $errors);
        $this->assertStringContainsString('response', $errors[0]);
        $this->assertStringContainsString('hi', $errors[0]);
    }

    public function testLogsErrorWithEmptyData(): void
    {
        $logger = new FakeLogger();
        $middleware = new FallbackMiddleware($logger);
        $middleware->process($this->createRequest(), $this->next());

        $errors = $logger->getMessages('error');
        $this->assertCount(1, $errors);
    }
}
