<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\DataFactory;

use Lav45\MockServer\DataFactory\ContentFactory;
use Lav45\MockServer\Parser\InlineParser;
use Lav45\MockServer\Parser\ParamParser;
use Lav45\MockServer\Parser\VariableParser;
use PHPUnit\Framework\TestCase;

final class ContentFactoryTest extends TestCase
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

    private function decodeBody(string $json): mixed
    {
        return \json_decode($json, true, flags: JSON_THROW_ON_ERROR);
    }

    public function testCreateWithDefaults(): void
    {
        $response = new ContentFactory()->create($this->createParser(), []);

        $this->assertSame(200, $response->status->value);
        $this->assertSame([], $response->headers->toArray());
        $this->assertSame('', $response->body->value);
    }

    public function testCreateWithJson(): void
    {
        $response = new ContentFactory()->create($this->createParser(), [
            'json' => ['id' => 1, 'name' => 'test'],
        ]);

        $this->assertSame(200, $response->status->value);
        $this->assertSame('application/json', $response->headers->toArray()['content-type']);
        $this->assertSame(['id' => 1, 'name' => 'test'], $this->decodeBody($response->body->value));
    }

    public function testCreateWithText(): void
    {
        $response = new ContentFactory()->create($this->createParser(), [
            'text' => 'hello world',
        ]);

        $this->assertSame(200, $response->status->value);
        $this->assertArrayNotHasKey('content-type', $response->headers->toArray());
        $this->assertSame('hello world', $response->body->value);
    }

    public function testCreateWithCustomStatus(): void
    {
        $response = new ContentFactory()->create($this->createParser(), [
            'status' => 201,
            'json' => ['created' => true],
        ]);

        $this->assertSame(201, $response->status->value);
        $this->assertSame(['created' => true], $this->decodeBody($response->body->value));
    }

    public function testCreateWithTextAndExplicitContentType(): void
    {
        $response = new ContentFactory()->create($this->createParser(), [
            'headers' => ['content-type' => 'text/plain; charset=utf-8'],
            'text' => 'OK',
        ]);

        $this->assertSame(200, $response->status->value);
        $this->assertSame('text/plain; charset=utf-8', $response->headers->toArray()['content-type']);
        $this->assertSame('OK', $response->body->value);
    }

    public function testCreateWithCustomHeaders(): void
    {
        $response = new ContentFactory()->create($this->createParser(), [
            'headers' => ['X-Foo' => 'bar', 'X-Baz' => 'qux'],
        ]);

        $headers = $response->headers->toArray();
        $this->assertSame('bar', $headers['X-Foo']);
        $this->assertSame('qux', $headers['X-Baz']);
    }

    public function testCreateWithJsonAndCustomHeaders(): void
    {
        $response = new ContentFactory()->create($this->createParser(), [
            'status' => 422,
            'headers' => ['X-Request-Id' => 'abc123'],
            'json' => ['error' => 'invalid'],
        ]);

        $this->assertSame(422, $response->status->value);
        $headers = $response->headers->toArray();
        $this->assertSame('application/json', $headers['content-type']);
        $this->assertSame('abc123', $headers['X-Request-Id']);
        $this->assertSame(['error' => 'invalid'], $this->decodeBody($response->body->value));
    }
}
