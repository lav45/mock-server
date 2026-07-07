<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Extension\Content;

use Lav45\MockServer\DataFactory\DataBuilder;
use Lav45\MockServer\Engine\Http\RequestHandler;
use Lav45\MockServer\Engine\Http\ServerRequest;
use Lav45\MockServer\Engine\Http\ServerResponse;
use Lav45\MockServer\Extension\Content\ContentFactory;
use Lav45\MockServer\Extension\Content\ContentMiddleware;
use Lav45\MockServer\Extension\Content\ContentResponder;
use Lav45\MockServer\Test\Unit\Components\CallableHandler;
use Lav45\MockServer\Test\Unit\Components\FakeServerRequest;
use PHPUnit\Framework\TestCase;

final class ContentMiddlewareTest extends TestCase
{
    private function createMiddleware(): ContentMiddleware
    {
        return new ContentMiddleware(new ContentFactory(new DataBuilder()), new ContentResponder());
    }

    private function createRequest(): ServerRequest
    {
        return new FakeServerRequest('GET', 'https://localhost/');
    }

    private function nextReturning(int $status): RequestHandler
    {
        return new CallableHandler(static fn(ServerRequest $r): ServerResponse => new ServerResponse($status));
    }

    // --- Passthrough ---

    public function testPassesThroughToNextWhenResponseTypeDoesNotMatch(): void
    {
        $request = $this->createRequest();
        $request->setAttribute('data', ['response' => ['type' => 'proxy']]);

        $response = ($this->createMiddleware())->process($request, $this->nextReturning(418));

        $this->assertSame(418, $response->getStatus());
    }

    public function testDoesNotCallNextWhenResponseTypeMatches(): void
    {
        $request = $this->createRequest();
        $request->setAttribute('data', ['response' => ['type' => 'content']]);

        $response = ($this->createMiddleware())->process($request, $this->nextReturning(418));

        $this->assertNotSame(418, $response->getStatus());
    }

    // --- Response building ---

    public function testReturnsDefaultResponseWithEmptyData(): void
    {
        $request = $this->createRequest();
        $request->setAttribute('data', ['response' => ['type' => 'content']]);

        $response = ($this->createMiddleware())->process($request, $this->nextReturning(418));

        $this->assertSame(200, $response->getStatus());
        $this->assertSame('', $response->getBody()->stream->read());
    }

    public function testReturnsJsonResponse(): void
    {
        $request = $this->createRequest();
        $request->setAttribute('data', [
            'response' => [
                'type' => 'content',
                'headers' => ['content-type' => 'application/json'],
                'body' => ['id' => 1, 'name' => 'test'],
            ],
        ]);

        $response = ($this->createMiddleware())->process($request, $this->nextReturning(418));

        $this->assertSame(200, $response->getStatus());
        $this->assertSame('application/json', $response->getHeader('content-type'));
        $this->assertSame('{"id":1,"name":"test"}', $response->getBody()->stream->read());
    }

    public function testReturnsTextResponse(): void
    {
        $request = $this->createRequest();
        $request->setAttribute('data', [
            'response' => ['type' => 'content', 'body' => 'hello world'],
        ]);

        $response = ($this->createMiddleware())->process($request, $this->nextReturning(418));

        $this->assertSame(200, $response->getStatus());
        $this->assertSame('hello world', $response->getBody()->stream->read());
    }

    public function testReturnsCustomStatus(): void
    {
        $request = $this->createRequest();
        $request->setAttribute('data', [
            'response' => ['type' => 'content', 'status' => 201, 'body' => ['created' => true]],
        ]);

        $response = ($this->createMiddleware())->process($request, $this->nextReturning(418));

        $this->assertSame(201, $response->getStatus());
    }

    // --- Attribute forwarding ---

    public function testUsesResponseKeyFromDataAttribute(): void
    {
        $request = $this->createRequest();
        $request->setAttribute('data', [
            'env' => ['ignored' => true],
            'response' => ['type' => 'content', 'body' => 'from response key'],
        ]);

        $response = ($this->createMiddleware())->process($request, $this->nextReturning(418));

        $this->assertSame('from response key', $response->getBody()->stream->read());
    }

    public function testDefaultsToEmptyResponseWhenResponseKeyMissing(): void
    {
        $request = $this->createRequest();
        $request->setAttribute('data', ['env' => ['x' => '1'], 'response' => ['type' => 'content']]);

        $response = ($this->createMiddleware())->process($request, $this->nextReturning(418));

        $this->assertSame(200, $response->getStatus());
        $this->assertSame('', $response->getBody()->stream->read());
    }

}
