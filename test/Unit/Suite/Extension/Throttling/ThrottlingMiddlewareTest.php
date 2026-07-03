<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Extension\Throttling;

use Lav45\MockServer\DataFactory\DataBuilder;
use Lav45\MockServer\Engine\Http\RequestHandler;
use Lav45\MockServer\Engine\Http\ServerRequest;
use Lav45\MockServer\Engine\Http\ServerResponse;
use Lav45\MockServer\Extension\Throttling\ThrottlingMiddleware;
use Lav45\MockServer\Parser\InlineParser;
use Lav45\MockServer\Parser\ParamParser;
use Lav45\MockServer\Test\Unit\Components\CallableHandler;
use Lav45\MockServer\Test\Unit\Components\FakeServerRequest;
use PHPUnit\Framework\Attributes\DataProvider;
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

    private function nextReturning(int $status): RequestHandler
    {
        return new CallableHandler(static fn(ServerRequest $_): ServerResponse => new ServerResponse($status));
    }

    public static function noDelayDataProvider(): array
    {
        return [
            'response key missing' => [[]],
            'delay key missing' => [['response' => ['text' => 'ok']]],
            'delay is zero' => [['response' => ['delay' => 0.0]]],
        ];
    }

    #[DataProvider('noDelayDataProvider')]
    public function testPassesThroughSynchronouslyWhenNoDelay(array $data): void
    {
        // Amp\delay() outside a fiber throws FiberError — no exception here proves no delay was applied
        $request = $this->createRequest($data);

        $middleware = new ThrottlingMiddleware(new DataBuilder());
        $response = $middleware->process($request, $this->nextReturning(200));

        $this->assertSame(200, $response->getStatus());
    }

    public function testAppliesDelayBeforeReturningResponse(): void
    {
        $delay = 0.01;
        $request = $this->createRequest(['response' => ['delay' => $delay]]);

        $elapsed = null;
        $capturedResponse = null;
        async(function () use ($request, &$elapsed, &$capturedResponse): void {
            $middleware = new ThrottlingMiddleware(new DataBuilder());
            $start = \microtime(true);
            $capturedResponse = $middleware->process($request, $this->nextReturning(200));
            $elapsed = \microtime(true) - $start;
        });
        EventLoop::run();

        $this->assertGreaterThanOrEqual(0.01, $elapsed);
        $this->assertSame(200, $capturedResponse->getStatus());
    }
}
