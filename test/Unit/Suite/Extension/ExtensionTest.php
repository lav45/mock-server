<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Extension;

use Lav45\MockServer\Extension\Cors\CorsExtension;
use Lav45\MockServer\Extension\Extension;
use Lav45\MockServer\Extension\WebHook\WebHookExtension;
use PHPUnit\Framework\TestCase;

final class ExtensionTest extends TestCase
{
    public function testDefaultConfigIsEmpty(): void
    {
        $extension = new Extension(CorsExtension::class);

        $this->assertSame(CorsExtension::class, $extension->class);
        $this->assertSame([], $extension->config);
    }

    public function testFromArrayWithConfig(): void
    {
        $extension = Extension::fromArray(['class' => CorsExtension::class, 'config' => ['allow_origin' => '*']]);

        $this->assertSame(CorsExtension::class, $extension->class);
        $this->assertSame(['allow_origin' => '*'], $extension->config);
    }

    public function testFromArrayDefaultsConfigToEmpty(): void
    {
        $extension = Extension::fromArray(['class' => WebHookExtension::class]);

        $this->assertSame(WebHookExtension::class, $extension->class);
        $this->assertSame([], $extension->config);
    }
}
