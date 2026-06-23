<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Middleware;

use Lav45\MockServer\Engine\Http\ServerRequest;
use Lav45\MockServer\Engine\Http\ServerResponse;
use Lav45\MockServer\Middleware\Middleware;
use Lav45\MockServer\Middleware\MiddlewareHandler;
use Lav45\MockServer\Middleware\MiddlewarePipeline;
use Lav45\MockServer\Test\Unit\Components\FakeServerRequest;
use PHPUnit\Framework\TestCase;

final class MiddlewarePipelineTest extends TestCase
{
    private function request(): ServerRequest
    {
        return new FakeServerRequest('GET', 'https://localhost/');
    }

    private function recording(\ArrayObject $log, string $name): Middleware
    {
        return new readonly class ($log, $name) implements Middleware {
            public function __construct(
                private \ArrayObject $log,
                private string       $name,
            ) {}

            public function process(ServerRequest $request, MiddlewareHandler $next): ServerResponse
            {
                $this->log->append($this->name);
                return $next->handle($request);
            }
        };
    }

    private function terminal(int $status): Middleware
    {
        return new readonly class ($status) implements Middleware {
            public function __construct(private int $status) {}

            public function process(ServerRequest $request, MiddlewareHandler $next): ServerResponse
            {
                return new ServerResponse($this->status);
            }
        };
    }

    public function testExecutesMiddlewareInOrderUntilOneStopsDelegating(): void
    {
        $log = new \ArrayObject();

        $pipeline = new MiddlewarePipeline(
            $this->recording($log, 'a'),
            $this->recording($log, 'b'),
            $this->terminal(204),
        );
        $response = $pipeline->handle($this->request());

        $this->assertSame(['a', 'b'], $log->getArrayCopy());
        $this->assertSame(204, $response->getStatus());
    }

    public function testFirstMiddlewareCanShortCircuit(): void
    {
        $log = new \ArrayObject();

        $pipeline = new MiddlewarePipeline(
            $this->terminal(201),
            $this->recording($log, 'never'),
        );
        $response = $pipeline->handle($this->request());

        $this->assertSame(201, $response->getStatus());
        $this->assertSame([], $log->getArrayCopy());
    }

    public function testThrowsWhenChainIsExhaustedWithoutResponse(): void
    {
        $log = new \ArrayObject();

        $pipeline = new MiddlewarePipeline(
            $this->recording($log, 'a'),
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageIsOrContains('Invalid middleware chain!');
        $pipeline->handle($this->request());
    }
}
