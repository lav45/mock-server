<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Extension;

use Lav45\MockServer\Engine\Http\RequestHandler;
use Lav45\MockServer\Engine\Http\ServerRequest;
use Lav45\MockServer\Engine\Http\ServerResponse;
use Lav45\MockServer\Extension\Middleware;
use Lav45\MockServer\Extension\Pipeline;
use Lav45\MockServer\Test\Unit\Components\FakeServerRequest;
use PHPUnit\Framework\TestCase;

final class PipelineTest extends TestCase
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

            public function process(ServerRequest $request, RequestHandler $next): ServerResponse
            {
                $this->log->append($this->name);
                return $next->handleRequest($request);
            }
        };
    }

    private function terminal(int $status): Middleware
    {
        return new readonly class ($status) implements Middleware {
            public function __construct(private int $status) {}

            public function process(ServerRequest $request, RequestHandler $next): ServerResponse
            {
                return new ServerResponse($this->status);
            }
        };
    }

    public function testExecutesMiddlewareInOrderUntilOneStopsDelegating(): void
    {
        $log = new \ArrayObject();

        $pipeline = new Pipeline(
            $this->recording($log, 'a'),
            $this->recording($log, 'b'),
            $this->terminal(204),
        );
        $response = $pipeline->handleRequest($this->request());

        $this->assertSame(['a', 'b'], $log->getArrayCopy());
        $this->assertSame(204, $response->getStatus());
    }

    public function testFirstMiddlewareCanShortCircuit(): void
    {
        $log = new \ArrayObject();

        $pipeline = new Pipeline(
            $this->terminal(201),
            $this->recording($log, 'never'),
        );
        $response = $pipeline->handleRequest($this->request());

        $this->assertSame(201, $response->getStatus());
        $this->assertSame([], $log->getArrayCopy());
    }

    public function testThrowsWhenChainIsExhaustedWithoutResponse(): void
    {
        $log = new \ArrayObject();

        $pipeline = new Pipeline(
            $this->recording($log, 'a'),
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageIsOrContains('Invalid middleware chain!');
        $pipeline->handleRequest($this->request());
    }
}
