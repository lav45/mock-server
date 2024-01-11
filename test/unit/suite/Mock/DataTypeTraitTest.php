<?php declare(strict_types=1);

namespace lav45\MockServer\test\unit\suite\Mock;

use lav45\MockServer\InvalidConfigException;
use lav45\MockServer\Mock\DataTypeTrait;
use PHPUnit\Framework\TestCase;

class DataTypeTraitTest extends TestCase
{
    private function createTestClass(): object
    {
        return new class {
            use DataTypeTrait;

            public function set(string $type): void
            {
                $this->setType($type);
            }
        };
    }

    public function testSet(): void
    {
        $testClass = $this->createTestClass();

        $this->assertNull($testClass->getType());
        $this->assertEquals('json', $testClass->getType('json'));

        $testClass->set('text');
        $this->assertEquals('text', $testClass->getType('json'));
    }

    public function testDoubleSet(): void
    {
        $this->expectException(InvalidConfigException::class);
        $testClass = $this->createTestClass();
        $testClass->set('text');
        $testClass->set('json');
    }
}