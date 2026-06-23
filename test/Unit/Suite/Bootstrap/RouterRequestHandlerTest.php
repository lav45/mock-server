<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Bootstrap;

use FastRoute\Dispatcher;
use Lav45\MockServer\Bootstrap\ErrorHandler;
use Lav45\MockServer\Bootstrap\RouterRequestHandler;
use Lav45\MockServer\Engine\Http\ServerRequest;
use Lav45\MockServer\Engine\Http\ServerResponse;
use Lav45\MockServer\Test\Unit\Components\CallableHandler;
use Lav45\MockServer\Test\Unit\Components\FakeServerRequest;
use PHPUnit\Framework\TestCase;

final class RouterRequestHandlerTest extends TestCase
{
    private ErrorHandler $errorHandler;

    protected function setUp(): void
    {
        $this->errorHandler = new ErrorHandler();
    }

    private function createRequest(string $method, string $url): ServerRequest
    {
        return new FakeServerRequest($method, $url);
    }

    private function handlerReturning(ServerResponse $response): CallableHandler
    {
        return new CallableHandler(static fn(ServerRequest $request): ServerResponse => $response);
    }

    public function testHandleRequestFound(): void
    {
        $expectedResponse = new ServerResponse(200);
        $handler = $this->handlerReturning($expectedResponse);

        $data = [true];
        $params = ['id' => '123'];
        $dispatcher = new FakeDispatcher([
            Dispatcher::FOUND, $data, $params,
        ]);

        $reactor = new RouterRequestHandler($this->errorHandler, $dispatcher, $handler);
        $request = $this->createRequest('GET', 'https://localhost/api/test/123');

        $response = $reactor->handleRequest($request);

        $this->assertSame($expectedResponse, $response);
        $this->assertSame($data, $request->getAttribute('data'));
        $this->assertSame($params, $request->getAttribute('params'));
    }

    public function testHandleRequestNotFound(): void
    {
        $handler = $this->handlerReturning(new ServerResponse(200));

        $dispatcher = new FakeDispatcher([
            Dispatcher::NOT_FOUND,
        ]);

        $reactor = new RouterRequestHandler($this->errorHandler, $dispatcher, $handler);
        $request = $this->createRequest('GET', 'https://localhost/not-found');

        $response = $reactor->handleRequest($request);

        $this->assertSame(404, $response->getStatus());
    }

    public function testHandleRequestMethodNotAllowed(): void
    {
        $handler = $this->handlerReturning(new ServerResponse(200));

        $dispatcher = new FakeDispatcher([
            Dispatcher::METHOD_NOT_ALLOWED,
            ['GET', 'POST'],
        ]);

        $reactor = new RouterRequestHandler($this->errorHandler, $dispatcher, $handler);
        $request = $this->createRequest('PUT', 'https://localhost/api/test');

        $response = $reactor->handleRequest($request);

        $this->assertSame(405, $response->getStatus());
        $this->assertSame('GET, POST', $response->getHeader('allow'));
    }
}

final readonly class FakeDispatcher implements Dispatcher
{
    public function __construct(private array $dispatchResult) {}

    public function dispatch($httpMethod, $uri): array
    {
        return $this->dispatchResult;
    }
}
