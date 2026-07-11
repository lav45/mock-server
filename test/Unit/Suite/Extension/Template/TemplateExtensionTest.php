<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Extension\Template;

use Lav45\MockServer\Engine\Http\ServerResponse;
use Lav45\MockServer\Extension\Container;
use Lav45\MockServer\Extension\ExtensionType;
use Lav45\MockServer\Extension\Template\TemplateExtension;
use Lav45\MockServer\Extension\Template\TemplateMiddleware;
use Lav45\MockServer\Parser\InlineParser;
use Lav45\MockServer\Parser\ParamParser;
use Lav45\MockServer\Parser\VariableParser;
use Lav45\MockServer\Test\Unit\Components\CallableHandler;
use Lav45\MockServer\Test\Unit\Components\FakeServerRequest;
use PHPUnit\Framework\TestCase;

final class TemplateExtensionTest extends TestCase
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

    public function testTypeIsApplication(): void
    {
        $this->assertSame(ExtensionType::Application, new TemplateExtension()->type());
    }

    public function testCreateReturnsTemplateMiddleware(): void
    {
        $middleware = new TemplateExtension()->create(new Container(), [
            'template' => ['greeting' => ['body' => 'hello']],
        ]);

        $this->assertInstanceOf(TemplateMiddleware::class, $middleware);
    }

    public function testCreateWithoutTemplateConfigReturnsMiddleware(): void
    {
        $middleware = new TemplateExtension()->create(new Container(), []);

        $this->assertInstanceOf(TemplateMiddleware::class, $middleware);
    }

    public function testCreatedMiddlewareExpandsConfiguredTemplate(): void
    {
        $middleware = new TemplateExtension()->create(new Container(), [
            'template' => ['greeting' => ['body' => 'hi {template.name}']],
        ]);

        $request = new FakeServerRequest();
        $request->setAttribute('parser', $this->createParser());
        $request->setAttribute('data', ['response' => ['$template.greeting' => ['name' => 'Bob']]]);

        $capturedData = [];
        $middleware->process($request, new CallableHandler(static function ($request) use (&$capturedData): ServerResponse {
            $capturedData = $request->getAttribute('data');
            return new ServerResponse(200);
        }));

        $this->assertSame(['response' => ['body' => 'hi Bob']], $capturedData);
    }
}
