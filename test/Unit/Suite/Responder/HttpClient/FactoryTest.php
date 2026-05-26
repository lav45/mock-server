<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Responder\HttpClient;

use Lav45\MockServer\Responder\HttpClient\Factory;
use Lav45\MockServer\Responder\HttpClient\HttpClient;
use PHPUnit\Framework\TestCase;

final class FactoryTest extends TestCase
{
    public function testCreateReturnsHttpClient(): void
    {
        $factory = new Factory();

        $client = $factory->create();

        $this->assertInstanceOf(HttpClient::class, $client);
    }

    public function testCreateReturnsNewInstanceEachCall(): void
    {
        $factory = new Factory();

        $first = $factory->create();
        $second = $factory->create();

        $this->assertNotSame($first, $second);
    }
}
