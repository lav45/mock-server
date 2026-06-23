<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Middleware;

use Lav45\MockServer\DataFactory\ParserFactory;
use Lav45\MockServer\Engine\Http\ServerRequest;
use Lav45\MockServer\Engine\Http\ServerResponse;
use Lav45\MockServer\Middleware\MiddlewareHandler;
use Lav45\MockServer\Middleware\PrepareMiddleware;
use Lav45\MockServer\Parser\InlineParser;
use Lav45\MockServer\Parser\ParamParser;
use Lav45\MockServer\Parser\VariableParser;
use Lav45\MockServer\Test\Unit\Components\CallableHandler;
use Lav45\MockServer\Test\Unit\Components\FakeServerRequest;
use PHPUnit\Framework\TestCase;

final class ParserMiddlewareTest extends TestCase
{
    private function createMiddleware(): PrepareMiddleware
    {
        $baseParser = new ParamParser(new class implements InlineParser {
            public function replace(mixed $data): mixed
            {
                return $data;
            }
        });
        return new PrepareMiddleware(new ParserFactory($baseParser));
    }

    private function createRequest(string $url = 'https://localhost/', array $params = []): ServerRequest
    {
        $request = new FakeServerRequest('GET', $url);
        $request->setAttribute('params', $params);
        return $request;
    }

    private function nextCapturing(VariableParser|null &$capturedParser): MiddlewareHandler
    {
        return new CallableHandler(static function (ServerRequest $request) use (&$capturedParser): ServerResponse {
            $capturedParser = $request->getAttribute('parser');
            return new ServerResponse(200);
        });
    }

    private function nextCapturingData(array|null &$capturedData): MiddlewareHandler
    {
        return new CallableHandler(static function (ServerRequest $request) use (&$capturedData): ServerResponse {
            $capturedData = $request->getAttribute('data');
            return new ServerResponse(200);
        });
    }

    public function testAlwaysCallsNext(): void
    {
        $request = $this->createRequest();
        $request->setAttribute('data', []);

        $called = false;
        $next = static function () use (&$called): ServerResponse {
            $called = true;
            return new ServerResponse(200);
        };

        $middleware = $this->createMiddleware();
        $middleware->process($request, new CallableHandler($next));

        $this->assertTrue($called);
    }

    public function testSetsParserAttributeOnRequest(): void
    {
        $request = $this->createRequest();
        $request->setAttribute('data', []);

        $capturedParser = null;
        $middleware = $this->createMiddleware();
        $middleware->process($request, $this->nextCapturing($capturedParser));

        $this->assertInstanceOf(VariableParser::class, $capturedParser);
    }

    public function testParserResolvesEnvFromData(): void
    {
        $request = $this->createRequest();
        $request->setAttribute('data', ['env' => ['token' => 'secret123']]);

        $capturedParser = null;
        $middleware = $this->createMiddleware();
        $middleware->process($request, $this->nextCapturing($capturedParser));

        $this->assertSame('secret123', $capturedParser->replace('{env.token}'));
    }

    public function testDefaultsToEmptyEnvWhenKeyMissing(): void
    {
        $request = $this->createRequest();
        $request->setAttribute('data', ['response' => ['text' => 'hi']]);

        $capturedParser = null;
        $middleware = $this->createMiddleware();
        $middleware->process($request, $this->nextCapturing($capturedParser));

        $this->assertInstanceOf(VariableParser::class, $capturedParser);
    }

    public function testParserResolvesRequestParams(): void
    {
        $request = $this->createRequest(params: ['id' => '42']);
        $request->setAttribute('data', []);

        $capturedParser = null;
        $middleware = $this->createMiddleware();
        $middleware->process($request, $this->nextCapturing($capturedParser));

        $this->assertSame('42', $capturedParser->replace('{request.params.id}'));
    }

    public function testResolvesDataAttribute(): void
    {
        $request = $this->createRequest(params: ['id' => '42']);
        $request->setAttribute('data', [
            'env' => ['token' => 'secret123'],
            'response' => [
                'body' => [
                    'id' => '{{request.params.id}}',
                    'token' => '{{env.token}}',
                ],
            ],
        ]);

        $capturedData = null;
        $middleware = $this->createMiddleware();
        $middleware->process($request, $this->nextCapturingData($capturedData));

        $this->assertSame(['id' => '42', 'token' => 'secret123'], $capturedData['response']['body']);
    }
}
