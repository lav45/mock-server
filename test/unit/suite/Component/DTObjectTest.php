<?php declare(strict_types=1);

namespace lav45\MockServer\test\unit\suite\Component;

use lav45\MockServer\Component\DTObject;
use PHPUnit\Framework\TestCase;

class DTObjectTest extends TestCase
{
    public function testIndex(): void
    {
        $testClass = new DTObjectTestClass();

        $this->assertNull($testClass->aaa);
        $this->assertNull($testClass->value);

        $testClass->setValue(1);
        $this->assertEquals(1, $testClass->getValue());

        $testClass->value = 2;
        $this->assertEquals(2, $testClass->getValue());

        $this->assertTrue(isset($testClass->value));
        $this->assertFalse(isset($testClass->aaa));
    }
}

/**
 * @property mixed $value
 */
class DTObjectTestClass extends DTObject {
    private mixed $value = null;

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function setValue($value): void
    {
        $this->value = $value;
    }
}