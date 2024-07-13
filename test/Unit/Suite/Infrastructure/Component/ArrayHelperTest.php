<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Infrastructure\Component;

use Lav45\MockServer\Infrastructure\Component\ArrayHelper;
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
}
