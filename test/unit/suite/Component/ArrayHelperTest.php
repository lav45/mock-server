<?php declare(strict_types=1);

namespace lav45\MockServer\test\unit\suite\Component;

use lav45\MockServer\Component\ArrayHelper;
use PHPUnit\Framework\TestCase;

class ArrayHelperTest extends TestCase
{
    public function testGetValue(): void
    {
        $data = [
            1 => true,
            2 => null,
            'a' => [
                'b' => 1
            ]
        ];

        $this->assertNull(ArrayHelper::getValue($data, 'd'));
        $this->assertNull(ArrayHelper::getValue($data, 'a.b.c'));
        $this->assertEquals(1, ArrayHelper::getValue($data, 'a.b'));
        $this->assertTrue(ArrayHelper::getValue($data, '1'));
        $this->assertNull(ArrayHelper::getValue($data, '2'));
    }
}