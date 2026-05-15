<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Middleware;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Lav45\MockServer\DataFactory\ContentFactory;
use Lav45\MockServer\Middleware\ContentMiddleware;
use Lav45\MockServer\Parser\InlineParser;
use Lav45\MockServer\Parser\ParamParser;
use Lav45\MockServer\Parser\VariableParser;
use Lav45\MockServer\Responder\ContentResponder;
use Lav45\MockServer\Test\Unit\Components\FakeHttpDriverClient;
use League\Uri\Http;
use PHPUnit\Framework\TestCase;

use function Amp\ByteStream\buffer;

final class ContentMiddlewareTest extends TestCase
{
    private function createMiddleware(): ContentMiddleware
    {
        return new ContentMiddleware(new ContentFactory(), new ContentResponder());
    }

    private function createRequest(): Request
    {
        return new Request(new FakeHttpDriverClient(), 'GET', Http::new('https://localhost/'));
    }

    private function createParser(array $data = []): VariableParser
    {
        $parser = new ParamParser(new class implements InlineParser {
            public function replace(mixed $data): mixed
            {
                return $data;
            }
        });
        return $data ? $parser->withData($data) : $parser;
    }

    private function nextReturning(int $status): \Closure
    {
        return static fn(Request $r): Response => new Response($status);
    }

    private function readBody(Response $response): string
    {
        return buffer($response->getBody());
    }

    // --- Passthrough ---

    public function testPassesThroughToNextWhenResponseTypeDoesNotMatch(): void
    {
        $request = $this->createRequest();
        $request->setAttribute('responseType', 'proxy');
        $request->setAttribute('parser', $this->createParser());
        $request->setAttribute('data', []);

        $response = ($this->createMiddleware())($request, $this->nextReturning(418));

        $this->assertSame(418, $response->getStatus());
    }

    public function testDoesNotCallNextWhenResponseTypeMatches(): void
    {
        $request = $this->createRequest();
        $request->setAttribute('responseType', ContentFactory::TYPE);
        $request->setAttribute('parser', $this->createParser());
        $request->setAttribute('data', []);

        $response = ($this->createMiddleware())($request, $this->nextReturning(418));

        $this->assertNotSame(418, $response->getStatus());
    }

    // --- Response building ---

    public function testReturnsDefaultResponseWithEmptyData(): void
    {
        $request = $this->createRequest();
        $request->setAttribute('responseType', ContentFactory::TYPE);
        $request->setAttribute('parser', $this->createParser());
        $request->setAttribute('data', []);

        $response = ($this->createMiddleware())($request, $this->nextReturning(418));

        $this->assertSame(200, $response->getStatus());
        $this->assertSame('', $this->readBody($response));
    }

    public function testReturnsJsonResponse(): void
    {
        $request = $this->createRequest();
        $request->setAttribute('responseType', ContentFactory::TYPE);
        $request->setAttribute('parser', $this->createParser());
        $request->setAttribute('data', [
            'response' => [
                'headers' => ['content-type' => 'application/json'],
                'body' => ['id' => 1, 'name' => 'test'],
            ],
        ]);

        $response = ($this->createMiddleware())($request, $this->nextReturning(418));

        $this->assertSame(200, $response->getStatus());
        $this->assertSame('application/json', $response->getHeader('content-type'));
        $this->assertSame('{"id":1,"name":"test"}', $this->readBody($response));
    }

    public function testReturnsTextResponse(): void
    {
        $request = $this->createRequest();
        $request->setAttribute('responseType', ContentFactory::TYPE);
        $request->setAttribute('parser', $this->createParser());
        $request->setAttribute('data', [
            'response' => ['body' => 'hello world'],
        ]);

        $response = ($this->createMiddleware())($request, $this->nextReturning(418));

        $this->assertSame(200, $response->getStatus());
        $this->assertSame('hello world', $this->readBody($response));
    }

    public function testReturnsCustomStatus(): void
    {
        $request = $this->createRequest();
        $request->setAttribute('responseType', ContentFactory::TYPE);
        $request->setAttribute('parser', $this->createParser());
        $request->setAttribute('data', [
            'response' => ['status' => 201, 'body' => ['created' => true]],
        ]);

        $response = ($this->createMiddleware())($request, $this->nextReturning(418));

        $this->assertSame(201, $response->getStatus());
    }

    // --- Attribute forwarding ---

    public function testUsesResponseKeyFromDataAttribute(): void
    {
        $request = $this->createRequest();
        $request->setAttribute('responseType', ContentFactory::TYPE);
        $request->setAttribute('parser', $this->createParser());
        $request->setAttribute('data', [
            'env' => ['ignored' => true],
            'response' => ['body' => 'from response key'],
        ]);

        $response = ($this->createMiddleware())($request, $this->nextReturning(418));

        $this->assertSame('from response key', $this->readBody($response));
    }

    public function testDefaultsToEmptyResponseWhenResponseKeyMissing(): void
    {
        $request = $this->createRequest();
        $request->setAttribute('responseType', ContentFactory::TYPE);
        $request->setAttribute('parser', $this->createParser());
        $request->setAttribute('data', ['env' => ['x' => '1']]);

        $response = ($this->createMiddleware())($request, $this->nextReturning(418));

        $this->assertSame(200, $response->getStatus());
        $this->assertSame('', $this->readBody($response));
    }

    public function testUsesParserFromRequestAttribute(): void
    {
        $parser = $this->createParser(['env' => ['greeting' => 'hi']]);

        $request = $this->createRequest();
        $request->setAttribute('responseType', ContentFactory::TYPE);
        $request->setAttribute('parser', $parser);
        $request->setAttribute('data', [
            'response' => ['body' => '{env.greeting}'],
        ]);

        $response = ($this->createMiddleware())($request, $this->nextReturning(418));

        $this->assertSame('hi', $this->readBody($response));
    }
}
