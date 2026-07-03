<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Extension\Cors;

use Lav45\MockServer\Extension\Cors\CorsConfig;
use PHPUnit\Framework\TestCase;

final class CorsConfigTest extends TestCase
{
    public function testDefaultsAllowAnyOrigin(): void
    {
        $config = new CorsConfig();

        $this->assertTrue($config->allowsAnyOrigin());
        $this->assertTrue($config->allowsOrigin('https://example.com'));
    }

    public function testExplicitOriginList(): void
    {
        $config = new CorsConfig(origins: ['https://a.com', 'https://b.com']);

        $this->assertFalse($config->allowsAnyOrigin());
        $this->assertTrue($config->allowsOrigin('https://a.com'));
        $this->assertFalse($config->allowsOrigin('https://c.com'));
    }

    public function testEmptyOriginListAllowsNothing(): void
    {
        $config = new CorsConfig(origins: []);

        $this->assertFalse($config->allowsAnyOrigin());
        $this->assertFalse($config->allowsOrigin('https://a.com'));
    }
}
