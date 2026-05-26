<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Middleware;

use Amp\Http\Server\Request;
use Lav45\MockServer\Middleware\FallbackMiddleware;
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

    public function testReturns404(): void
    {
        $middleware = new FallbackMiddleware();
        $response = $middleware($this->createRequest());

        $this->assertSame(404, $response->getStatus());
    }

    public function testLogsErrorWithSerializedData(): void
    {
        $logger = new FakeLogger();
        $middleware = new FallbackMiddleware($logger);
        $middleware($this->createRequest(['response' => ['text' => 'hi']]));

        $errors = $logger->getMessages('error');
        $this->assertCount(1, $errors);
        $this->assertStringContainsString('response', $errors[0]);
        $this->assertStringContainsString('hi', $errors[0]);
    }

    public function testLogsErrorWithEmptyData(): void
    {
        $logger = new FakeLogger();
        $middleware = new FallbackMiddleware($logger);
        $middleware($this->createRequest());

        $errors = $logger->getMessages('error');
        $this->assertCount(1, $errors);
    }
}
