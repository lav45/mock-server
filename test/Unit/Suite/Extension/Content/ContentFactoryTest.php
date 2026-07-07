<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Extension\Content;

use Lav45\MockServer\DataFactory\DataBuilder;
use Lav45\MockServer\Extension\Content\ContentFactory;
use PHPUnit\Framework\TestCase;

final class ContentFactoryTest extends TestCase
{
    private function decodeBody(string $json): mixed
    {
        return \json_decode($json, true, flags: JSON_THROW_ON_ERROR);
    }

    public function testHasMatchesContentType(): void
    {
        $this->assertTrue(new ContentFactory(new DataBuilder())->has(['type' => 'content']));
    }

    public function testHasMatchesWhenTypeMissing(): void
    {
        $this->assertTrue(new ContentFactory(new DataBuilder())->has([]));
    }

    public function testHasDoesNotMatchOtherType(): void
    {
        $this->assertFalse(new ContentFactory(new DataBuilder())->has(['type' => 'proxy']));
    }

    public function testCreateWithDefaults(): void
    {
        $response = new ContentFactory(new DataBuilder())->create([]);

        $this->assertSame(200, $response->status->value);
        $this->assertSame([], $response->headers->toArray());
        $this->assertSame('', $response->body->stream->read());
    }

    public function testCreateWithJson(): void
    {
        $response = new ContentFactory(new DataBuilder())->create([
            'headers' => ['content-type' => 'application/json'],
            'body' => ['id' => 1, 'name' => 'test'],
        ]);

        $this->assertSame(200, $response->status->value);
        $this->assertSame('application/json', $response->headers->toArray()['content-type']);
        $this->assertSame(['id' => 1, 'name' => 'test'], $this->decodeBody($response->body->stream->read()));
    }

    public function testCreateWithJsonBodyDoesNotOverrideExplicitContentTypeHeader(): void
    {
        $response = new ContentFactory(new DataBuilder())->create([
            'headers' => ['content-type' => 'application/ld+json'],
            'body' => ['id' => 1],
        ]);

        $this->assertSame('application/ld+json', $response->headers->toArray()['content-type']);
    }

    public function testCreateWithText(): void
    {
        $response = new ContentFactory(new DataBuilder())->create([
            'body' => 'hello world',
        ]);

        $this->assertSame(200, $response->status->value);
        $this->assertArrayNotHasKey('content-type', $response->headers->toArray());
        $this->assertSame('hello world', $response->body->stream->read());
    }

    public function testCreateWithCustomStatus(): void
    {
        $response = new ContentFactory(new DataBuilder())->create([
            'status' => 201,
            'body' => ['created' => true],
        ]);

        $this->assertSame(201, $response->status->value);
        $this->assertSame(['created' => true], $this->decodeBody($response->body->stream->read()));
    }

    public function testCreateWithTextAndExplicitContentType(): void
    {
        $response = new ContentFactory(new DataBuilder())->create([
            'headers' => ['content-type' => 'text/plain; charset=utf-8'],
            'body' => 'OK',
        ]);

        $this->assertSame(200, $response->status->value);
        $this->assertSame('text/plain; charset=utf-8', $response->headers->toArray()['content-type']);
        $this->assertSame('OK', $response->body->stream->read());
    }

    public function testCreateWithCustomHeaders(): void
    {
        $response = new ContentFactory(new DataBuilder())->create([
            'headers' => ['X-Foo' => 'bar', 'X-Baz' => 'qux'],
        ]);

        $headers = $response->headers->toArray();
        $this->assertSame('bar', $headers['X-Foo']);
        $this->assertSame('qux', $headers['X-Baz']);
    }

    public function testCreateWithJsonAndCustomHeaders(): void
    {
        $response = new ContentFactory(new DataBuilder())->create([
            'status' => 422,
            'headers' => [
                'content-type' => 'application/json',
                'X-Request-Id' => 'abc123',
            ],
            'body' => ['error' => 'invalid'],
        ]);

        $this->assertSame(422, $response->status->value);
        $headers = $response->headers->toArray();
        $this->assertSame('application/json', $headers['content-type']);
        $this->assertSame('abc123', $headers['X-Request-Id']);
        $this->assertSame(['error' => 'invalid'], $this->decodeBody($response->body->stream->read()));
    }
}
