<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Bootstrap;

use Amp\Http\HttpStatus;
use Amp\Http\Server\ErrorHandler;
use Amp\Http\Server\Request as HttpRequest;
use Amp\Http\Server\RequestHandler as RequestHandlerInterface;
use Amp\Http\Server\Response as HttpResponse;
use FastRoute\Dispatcher;
use Lav45\MockServer\Bootstrap\RouterRequestHandler;
use Lav45\MockServer\Bootstrap\Watcher;
use Lav45\MockServer\Test\Unit\Components\FakeHttpDriverClient;
use League\Uri\Http;
use PHPUnit\Framework\TestCase;

final class RouterRequestHandlerTest extends TestCase
{
    private ErrorHandler $errorHandler;

    protected function setUp(): void
    {
        $this->errorHandler = new FakeErrorHandler();
    }

    private function createRequest(string $method, string $url): HttpRequest
    {
        $clientFake = new FakeHttpDriverClient();
        $uriReal = Http::new($url);

        return new HttpRequest($clientFake, $method, $uriReal);
    }

    public function testHandleRequestFound(): void
    {
        $expectedResponse = new HttpResponse(HttpStatus::OK);
        $handler = new FakeRequestHandler($expectedResponse);

        $data = [true];
        $params = ['id' => '123'];
        $dispatcher = new FakeDispatcher([
            Dispatcher::FOUND, $data, $params,
        ]);

        $reactor = new RouterRequestHandler($this->errorHandler, new FakeWatcher($dispatcher), $handler);
        $request = $this->createRequest('GET', 'http://localhost/api/test/123');

        $response = $reactor->handleRequest($request);

        $this->assertSame($expectedResponse, $response);
        $this->assertSame($data, $request->getAttribute('data'));
        $this->assertSame($params, $request->getAttribute('params'));
    }

    public function testHandleRequestNotFound(): void
    {
        $expectedResponse = new HttpResponse(HttpStatus::OK);
        $handler = new FakeRequestHandler($expectedResponse);

        $dispatcher = new FakeDispatcher([
            Dispatcher::NOT_FOUND,
        ]);
        $watcher = new FakeWatcher($dispatcher);

        $reactor = new RouterRequestHandler($this->errorHandler, $watcher, $handler);
        $request = $this->createRequest('GET', 'http://localhost/not-found');

        $response = $reactor->handleRequest($request);

        $this->assertSame(HttpStatus::NOT_FOUND, $response->getStatus());
    }

    public function testHandleRequestMethodNotAllowed(): void
    {
        $expectedResponse = new HttpResponse(HttpStatus::OK);
        $handler = new FakeRequestHandler($expectedResponse);

        $dispatcher = new FakeDispatcher([
            Dispatcher::METHOD_NOT_ALLOWED,
            ['GET', 'POST'],
        ]);
        $watcher = new FakeWatcher($dispatcher);

        $reactor = new RouterRequestHandler($this->errorHandler, $watcher, $handler);
        $request = $this->createRequest('PUT', 'http://localhost/api/test');

        $response = $reactor->handleRequest($request);

        $this->assertSame(HttpStatus::METHOD_NOT_ALLOWED, $response->getStatus());
        $this->assertTrue($response->hasHeader('allow'));
        $this->assertSame('GET, POST', $response->getHeader('allow'));
    }
}

final class FakeErrorHandler implements ErrorHandler
{
    public function handleError(int $status, string|null $reason = null, HttpRequest|null $request = null): HttpResponse
    {
        return new HttpResponse(
            status: $status,
            headers: ['content-type' => 'text/plain'],
            body: $reason ?? '',
        );
    }
}

final readonly class FakeWatcher implements Watcher
{
    public function __construct(private Dispatcher $dispatcher) {}

    public function getDispatcher(): Dispatcher
    {
        return $this->dispatcher;
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

final readonly class FakeRequestHandler implements RequestHandlerInterface
{
    public function __construct(private HttpResponse $response) {}

    public function handleRequest(HttpRequest $request): HttpResponse
    {
        return $this->response;
    }
}
