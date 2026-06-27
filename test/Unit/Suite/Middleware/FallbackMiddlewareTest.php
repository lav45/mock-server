<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Middleware;

use Lav45\MockServer\Engine\Http\ServerRequest;
use Lav45\MockServer\Engine\Http\ServerResponse;
use Lav45\MockServer\Middleware\FallbackMiddleware;
use Lav45\MockServer\Middleware\MiddlewareHandler;
use Lav45\MockServer\Test\Unit\Components\CallableHandler;
use Lav45\MockServer\Test\Unit\Components\FakeLogger;
use Lav45\MockServer\Test\Unit\Components\FakeServerRequest;
use PHPUnit\Framework\TestCase;

final class FallbackMiddlewareTest extends TestCase
{
    private function createRequest(array $data = []): ServerRequest
    {
        $request = new FakeServerRequest('GET', 'https://localhost/');
        $request->setAttribute('data', $data);
        return $request;
    }

    private function next(): MiddlewareHandler
    {
        return new CallableHandler(static fn(ServerRequest $request): ServerResponse => new ServerResponse(500));
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

    public function testLogsErrorWithUnescapedSlashes(): void
    {
        $logger = new FakeLogger();
        $middleware = new FallbackMiddleware($logger);
        $middleware->process($this->createRequest(['url' => 'https://example.com/path']), $this->next());

        $errors = $logger->getMessages('error');
        $this->assertStringContainsString('https://example.com/path', $errors[0]);
    }

    public function testLogsErrorIsPrettyPrinted(): void
    {
        $logger = new FakeLogger();
        $middleware = new FallbackMiddleware($logger);
        $middleware->process($this->createRequest(['key' => 'value']), $this->next());

        $errors = $logger->getMessages('error');
        $this->assertStringContainsString("\n", $errors[0]);
    }
}
