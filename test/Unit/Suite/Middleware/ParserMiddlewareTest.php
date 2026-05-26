<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Middleware;

use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Lav45\MockServer\DataFactory\ParserFactory;
use Lav45\MockServer\Middleware\ParserMiddleware;
use Lav45\MockServer\Parser\InlineParser;
use Lav45\MockServer\Parser\ParamParser;
use Lav45\MockServer\Parser\VariableParser;
use Lav45\MockServer\Test\Unit\Components\FakeHttpDriverClient;
use League\Uri\Http;
use PHPUnit\Framework\TestCase;

final class ParserMiddlewareTest extends TestCase
{
    private function createMiddleware(): ParserMiddleware
    {
        $baseParser = new ParamParser(new class implements InlineParser {
            public function replace(mixed $data): mixed
            {
                return $data;
            }
        });
        return new ParserMiddleware(new ParserFactory($baseParser));
    }

    private function createRequest(string $url = 'https://localhost/', array $params = []): Request
    {
        $request = new Request(new FakeHttpDriverClient(), 'GET', Http::new($url));
        $request->setAttribute('body', '');
        $request->setAttribute('params', $params);
        return $request;
    }

    private function nextCapturing(VariableParser|null &$capturedParser): \Closure
    {
        return static function (Request $request) use (&$capturedParser): Response {
            $capturedParser = $request->getAttribute('parser');
            return new Response(200);
        };
    }

    public function testAlwaysCallsNext(): void
    {
        $request = $this->createRequest();
        $request->setAttribute('data', []);

        $called = false;
        $next = static function () use (&$called): Response {
            $called = true;
            return new Response(200);
        };

        $middleware = $this->createMiddleware();
        $middleware($request, $next);

        $this->assertTrue($called);
    }

    public function testSetsParserAttributeOnRequest(): void
    {
        $request = $this->createRequest();
        $request->setAttribute('data', []);

        $capturedParser = null;
        $middleware = $this->createMiddleware();
        $middleware($request, $this->nextCapturing($capturedParser));

        $this->assertInstanceOf(VariableParser::class, $capturedParser);
    }

    public function testParserResolvesEnvFromData(): void
    {
        $request = $this->createRequest();
        $request->setAttribute('data', ['env' => ['token' => 'secret123']]);

        $capturedParser = null;
        $middleware = $this->createMiddleware();
        $middleware($request, $this->nextCapturing($capturedParser));

        $this->assertSame('secret123', $capturedParser->replace('{env.token}'));
    }

    public function testDefaultsToEmptyEnvWhenKeyMissing(): void
    {
        $request = $this->createRequest();
        $request->setAttribute('data', ['response' => ['text' => 'hi']]);

        $capturedParser = null;
        $middleware = $this->createMiddleware();
        $middleware($request, $this->nextCapturing($capturedParser));

        $this->assertInstanceOf(VariableParser::class, $capturedParser);
    }

    public function testParserResolvesRequestParams(): void
    {
        $request = $this->createRequest(params: ['id' => '42']);
        $request->setAttribute('data', []);

        $capturedParser = null;
        $middleware = $this->createMiddleware();
        $middleware($request, $this->nextCapturing($capturedParser));

        $this->assertSame('42', $capturedParser->replace('{request.params.id}'));
    }
}
