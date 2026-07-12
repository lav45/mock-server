<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Domain\ValueObject;

use Lav45\MockServer\Domain\ValueObject\Body;
use Lav45\MockServer\Domain\ValueObject\StringStream;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class BodyTest extends TestCase
{
    #[DataProvider('newDataProvider')]
    public function testNew(string|array $data, bool $expectedIsJson, string $expectedRead): void
    {
        $body = Body::new($data);
        $this->assertSame($expectedIsJson, $body->isJson);
        $this->assertSame($expectedRead, $body->stream->read());
    }

    public static function newDataProvider(): array
    {
        return [
            'array is json' => [
                ['id' => 1, 'name' => 'test'],
                true,
                '{"id":1,"name":"test"}',
            ],
            'array keeps slashes and unicode' => [
                ['url' => 'http://example.com/path', 'name' => 'Кто'],
                true,
                '{"url":"http://example.com/path","name":"Кто"}',
            ],
            'valid json string is json' => [
                '{"id":1}',
                true,
                '{"id":1}',
            ],
            'plain string is not json' => [
                'raw body content',
                false,
                'raw body content',
            ],
            'empty string is not json' => [
                '',
                false,
                '',
            ],
        ];
    }

    public function testNewFromStreamIsNotJson(): void
    {
        $stream = new StringStream('{"id":1}');
        $body = Body::new($stream);
        $this->assertFalse($body->isJson);
        $this->assertSame($stream, $body->stream);
    }
}
