<?php declare(strict_types=1);

namespace lav45\MockServer\test\unit\suite\Domain\ValueObject\Response;

use lav45\MockServer\Domain\ValueObject\Response\Delay;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DelayTest extends TestCase
{
    #[DataProvider('initDataProvider')]
    public function testInit($actual, $expected): void
    {
        $value = Delay::new($actual)->value;

        $this->assertSame($expected, $value);
    }

    public static function initDataProvider(): array
    {
        return [
            [0, 0.0],
            [1, 1.0],
            [0.0, 0.0],
            [1.0, 1.0],
            ['0', 0.0],
            ['1', 1.0],
            ['0.0', 0.0],
            ['1.0', 1.0],
        ];
    }

    public function testException(): void
    {
        $this->expectException(\AssertionError::class);
        Delay::new(-1);
    }
}
