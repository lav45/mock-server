<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Parser;

use Lav45\MockServer\Helper\ArrayHelper;
use PHPUnit\Framework\TestCase;

final class ArrayHelperTest extends TestCase
{
    public function testGetValue(): void
    {
        $data = [
            1 => true,
            2 => null,
            'a' => [
                'b' => 1,
            ],
        ];

        $this->assertNull(ArrayHelper::getValue($data, 'd'));
        $this->assertNull(ArrayHelper::getValue($data, 'a.b.c'));
        $this->assertEquals(1, ArrayHelper::getValue($data, 'a.b'));
        $this->assertTrue(ArrayHelper::getValue($data, '1'));
        $this->assertNull(ArrayHelper::getValue($data, '2'));
    }

    public function testMapWithNonStringNonArrayValues(): void
    {
        $data = [
            'integer' => 42,
            'boolean' => true,
            'float'   => 3.14,
            'null'    => null,
            'object'  => new \stdClass(),
            'nested'  => [
                'deep_int' => 100,
                'deep_string' => 'hello',
            ],
        ];

        $callback = static fn($value) => $value;
        $result = ArrayHelper::map($data, $callback);

        $this->assertSame($data, $result);
    }
}
