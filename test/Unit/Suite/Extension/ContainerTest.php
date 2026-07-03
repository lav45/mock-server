<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Extension;

use Lav45\MockServer\Extension\Container;
use Lav45\MockServer\Extension\NotFoundException;
use PHPUnit\Framework\TestCase;

final class ContainerTest extends TestCase
{
    public function testGetReturnsRegisteredService(): void
    {
        $service = new \stdClass();
        $container = new Container([\stdClass::class => $service]);

        $this->assertSame($service, $container->get(\stdClass::class));
    }

    public function testHasReflectsRegistration(): void
    {
        $container = new Container([\stdClass::class => new \stdClass()]);

        $this->assertTrue($container->has(\stdClass::class));
        $this->assertFalse($container->has(\ArrayObject::class));
    }

    public function testGetThrowsForUnknownService(): void
    {
        $container = new Container();

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessageIsOrContains('Service not found: ' . \ArrayObject::class);
        $container->get(\ArrayObject::class);
    }
}
