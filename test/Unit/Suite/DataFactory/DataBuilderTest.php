<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\DataFactory;

use Lav45\MockServer\DataFactory\DataBuilder;
use Lav45\MockServer\Parser\InlineParser;
use Lav45\MockServer\Parser\ParamParser;
use Lav45\MockServer\Parser\VariableParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DataBuilderTest extends TestCase
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

    #[DataProvider('createDelayDataProvider')]
    public function testCreateDelay(array $data, float $expected): void
    {
        $delay = new DataBuilder($this->createParser(), $data)->createDelay();
        $this->assertSame($expected, $delay->value);
    }

    public static function createDelayDataProvider(): array
    {
        return [
            'default' => [[], 0.0],
            'integer' => [['delay' => 2], 2.0],
            'string float' => [['delay' => '1.5'], 1.5],
        ];
    }

    #[DataProvider('createStatusDataProvider')]
    public function testCreateStatus(array $data, int $expected): void
    {
        $status = new DataBuilder($this->createParser(), $data)->createStatus();
        $this->assertSame($expected, $status->value);
    }

    public static function createStatusDataProvider(): array
    {
        return [
            'default' => [[], 200],
            'integer' => [['status' => 418], 418],
            'string' => [['status' => '404'], 404],
        ];
    }

    public function testCreateHeadersDefaultsToEmpty(): void
    {
        $headers = new DataBuilder($this->createParser(), [])->createHeaders();
        $this->assertSame([], $headers->toArray());
    }

    public function testCreateHeadersWithCustomHeaders(): void
    {
        $headers = new DataBuilder($this->createParser(), [
            'headers' => ['X-Foo' => 'bar', 'X-Baz' => 'qux'],
        ])->createHeaders();
        $this->assertSame(['X-Foo' => 'bar', 'X-Baz' => 'qux'], $headers->toArray());
    }

    public function testCreateHeadersAppendsExtraHeaders(): void
    {
        $headers = new DataBuilder($this->createParser(), [
            'headers' => ['X-Foo' => 'bar'],
        ])->createHeaders(appendHeaders: ['X-Extra' => 'val']);
        $this->assertSame(['X-Foo' => 'bar', 'X-Extra' => 'val'], $headers->toArray());
    }

    public function testCreateHeadersFiltersReservedAppendHeaders(): void
    {
        $filterHeaders = ['host', 'content-length', 'connection', 'keep-alive', 'transfer-encoding'];
        $headers = new DataBuilder($this->createParser(), [], $filterHeaders)->createHeaders(
            appendHeaders: [
                'X-Keep' => 'yes',
                'host' => 'example.com',
                'content-length' => '100',
                'connection' => 'keep-alive',
                'keep-alive' => 'timeout=5',
                'transfer-encoding' => 'chunked',
            ],
        );
        $this->assertSame(['X-Keep' => 'yes'], $headers->toArray());
    }

    public function testCreateHeadersWithCustomFilterHeaders(): void
    {
        $headers = new DataBuilder($this->createParser(), [], ['x-internal'])->createHeaders(
            appendHeaders: ['x-internal' => 'secret', 'x-public' => 'visible'],
        );
        $this->assertSame(['x-public' => 'visible'], $headers->toArray());
    }

    public function testCreateBodyContentDefaultsToEmpty(): void
    {
        $body = new DataBuilder($this->createParser(), [])->createBodyContent();
        $this->assertNull($body);
    }

    public function testCreateBodyContentWithString(): void
    {
        $body = new DataBuilder($this->createParser(), ['content' => 'hello'])->createBodyContent();
        $this->assertSame('hello', $body->value);
    }

    public function testCreateBodyContentWithArray(): void
    {
        $body = new DataBuilder($this->createParser(), ['content' => ['key' => 'val']])->createBodyContent();
        $this->assertSame(['key' => 'val'], \json_decode($body->value, true, flags: JSON_THROW_ON_ERROR));
    }

    public function testCreateBodyDefaultsToEmpty(): void
    {
        $body = new DataBuilder($this->createParser(), [])->createBody();
        $this->assertSame('', $body->value);
    }

    public function testCreateBodyWithBody(): void
    {
        $body = new DataBuilder($this->createParser(), ['body' => 'hello world'])->createBody();
        $this->assertSame('hello world', $body->value);

        $body = new DataBuilder($this->createParser(), ['body' => ['id' => 1]])->createBody();
        $this->assertSame(['id' => 1], \json_decode($body->value, true, flags: JSON_THROW_ON_ERROR));
    }

    public function testCreateUrlThrowsWhenNoUrlKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new DataBuilder($this->createParser(), [])->createUrl();
    }

    #[DataProvider('createUrlDataProvider')]
    public function testCreateUrl(string $url, array $get, string $expected): void
    {
        $newUrl = new DataBuilder($this->createParser(), ['url' => $url])->createUrl($get)->value;
        $this->assertSame($expected, $newUrl);
    }

    public static function createUrlDataProvider(): array
    {
        return [
            'url only, no get' => ['https://localhost/path', [], 'https://localhost/path'],
            'append to clean url' => ['https://localhost', ['id' => 100], 'https://localhost?id=100'],
            'existing param wins' => ['https://localhost?id=50', ['id' => 100], 'https://localhost?id=50'],
            'append new param to query' => ['https://localhost?id=50', ['type' => 'test'], 'https://localhost?id=50&type=test'],
        ];
    }

    #[DataProvider('createMethodDataProvider')]
    public function testCreateMethod(array $data, string $expected): void
    {
        $method = new DataBuilder($this->createParser(), $data)->createMethod();
        $this->assertSame($expected, $method->value);
    }

    public static function createMethodDataProvider(): array
    {
        return [
            'default' => [[], 'POST'],
            'uppercase' => [['method' => 'GET'], 'GET'],
            'lowercase' => [['method' => 'delete'], 'DELETE'],
        ];
    }

    #[DataProvider('createItemsDataProvider')]
    public function testCreateItems(array $data, array $expected): void
    {
        $items = new DataBuilder($this->createParser(), $data)->createItems();
        $this->assertSame($expected, $items);
    }

    public static function createItemsDataProvider(): array
    {
        return [
            'default' => [[], []],
            'data list' => [
                ['items' => [
                    ['id' => 1],
                    ['id' => 2],
                ]],
                [
                    ['id' => 1],
                    ['id' => 2],
                ],
            ],
        ];
    }

    public function testCreateItemsFromFile(): void
    {
        $items = [['id' => 1], ['id' => 2]];
        $tempFile = \sys_get_temp_dir() . '/test_items_' . \uniqid('', true) . '.json';
        \file_put_contents($tempFile, \json_encode($items, JSON_THROW_ON_ERROR));

        try {
            $result = new DataBuilder($this->createParser(), ['file' => $tempFile])->createItems();
            $this->assertSame($items, $result);
        } finally {
            if (\file_exists($tempFile)) {
                \unlink($tempFile);
            }
        }
    }
}
