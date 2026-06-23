<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Driver;

use Lav45\MockServer\Driver\HttpClient;
use Lav45\MockServer\Driver\HttpClientFactory;
use PHPUnit\Framework\TestCase;

final class HttpClientFactoryTest extends TestCase
{
    public function testCreateReturnsHttpClient(): void
    {
        $factory = new HttpClientFactory();

        $client = $factory->create();

        $this->assertInstanceOf(HttpClient::class, $client);
    }

    public function testCreateReturnsNewInstanceEachCall(): void
    {
        $factory = new HttpClientFactory();

        $first = $factory->create();
        $second = $factory->create();

        $this->assertNotSame($first, $second);
    }
}
