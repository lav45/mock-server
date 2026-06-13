<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Middleware;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Lav45\MockServer\DataFactory\ContentFactory;
use Lav45\MockServer\DataFactory\DataBuilder;
use Lav45\MockServer\Middleware\ContentMiddleware;
use Lav45\MockServer\Middleware\MiddlewareHandler;
use Lav45\MockServer\Responder\ContentResponder;
use Lav45\MockServer\Test\Unit\Components\CallableHandler;
use Lav45\MockServer\Test\Unit\Components\FakeHttpDriverClient;
use League\Uri\Http;
use PHPUnit\Framework\TestCase;

use function Amp\ByteStream\buffer;

final class ContentMiddlewareTest extends TestCase
{
    private function createMiddleware(): ContentMiddleware
    {
        return new ContentMiddleware(new ContentFactory(new DataBuilder()), new ContentResponder());
    }

    private function createRequest(): Request
    {
        return new Request(new FakeHttpDriverClient(), 'GET', Http::new('https://localhost/'));
    }

    private function nextReturning(int $status): MiddlewareHandler
    {
        return new CallableHandler(static fn(Request $r): Response => new Response($status));
    }

    private function readBody(Response $response): string
    {
        return buffer($response->getBody());
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
        $this->assertSame('', $this->readBody($response));
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
        $this->assertSame('{"id":1,"name":"test"}', $this->readBody($response));
    }

    public function testReturnsTextResponse(): void
    {
        $request = $this->createRequest();
        $request->setAttribute('data', [
            'response' => ['type' => 'content', 'body' => 'hello world'],
        ]);

        $response = ($this->createMiddleware())->process($request, $this->nextReturning(418));

        $this->assertSame(200, $response->getStatus());
        $this->assertSame('hello world', $this->readBody($response));
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

        $this->assertSame('from response key', $this->readBody($response));
    }

    public function testDefaultsToEmptyResponseWhenResponseKeyMissing(): void
    {
        $request = $this->createRequest();
        $request->setAttribute('data', ['env' => ['x' => '1'], 'response' => ['type' => 'content']]);

        $response = ($this->createMiddleware())->process($request, $this->nextReturning(418));

        $this->assertSame(200, $response->getStatus());
        $this->assertSame('', $this->readBody($response));
    }

}
