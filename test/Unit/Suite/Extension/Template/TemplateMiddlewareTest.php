<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Extension\Template;

use Lav45\MockServer\Engine\Http\ServerRequest;
use Lav45\MockServer\Engine\Http\ServerResponse;
use Lav45\MockServer\Extension\Template\TemplateMiddleware;
use Lav45\MockServer\Extension\Template\TemplateResolver;
use Lav45\MockServer\Parser\InlineParser;
use Lav45\MockServer\Parser\ParamParser;
use Lav45\MockServer\Parser\VariableParser;
use Lav45\MockServer\Test\Unit\Components\CallableHandler;
use Lav45\MockServer\Test\Unit\Components\FakeServerRequest;
use PHPUnit\Framework\TestCase;

final class TemplateMiddlewareTest extends TestCase
{
    private function createParser(): VariableParser
    {
        return new ParamParser(new class implements InlineParser {
            public function replace(mixed $data): mixed
            {
                return $data;
            }
        });
    }

    private function nextCapturing(array &$capturedData): CallableHandler
    {
        return new CallableHandler(static function (ServerRequest $request) use (&$capturedData): ServerResponse {
            $capturedData = $request->getAttribute('data');
            return new ServerResponse(200);
        });
    }

    public function testResolvesDataAndContinuesPipeline(): void
    {
        $middleware = new TemplateMiddleware(
            new TemplateResolver(['greeting' => ['body' => 'hi {template.name}']]),
        );

        $request = new FakeServerRequest();
        $request->setAttribute('parser', $this->createParser());
        $request->setAttribute('data', ['response' => ['$template.greeting' => ['name' => 'Bob']]]);

        $capturedData = [];
        $response = $middleware->process($request, $this->nextCapturing($capturedData));

        $this->assertSame(200, $response->getStatus());
        $this->assertSame(['response' => ['body' => 'hi Bob']], $capturedData);
    }

    public function testStoresResolvedDataBackOnRequest(): void
    {
        $middleware = new TemplateMiddleware(
            new TemplateResolver(['greeting' => ['body' => 'hello']]),
        );

        $request = new FakeServerRequest();
        $request->setAttribute('parser', $this->createParser());
        $request->setAttribute('data', ['response' => ['$template.greeting' => []]]);

        $middleware->process($request, new CallableHandler(fn() => new ServerResponse(200)));

        $this->assertSame(['response' => ['body' => 'hello']], $request->getAttribute('data'));
    }

    public function testPassesDataUnchangedWhenNoTemplateKeys(): void
    {
        $middleware = new TemplateMiddleware(new TemplateResolver([]));

        $request = new FakeServerRequest();
        $request->setAttribute('parser', $this->createParser());
        $request->setAttribute('data', ['response' => ['type' => 'content', 'body' => 'plain']]);

        $capturedData = [];
        $middleware->process($request, $this->nextCapturing($capturedData));

        $this->assertSame(['response' => ['type' => 'content', 'body' => 'plain']], $capturedData);
    }
}
