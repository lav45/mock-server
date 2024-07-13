<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Domain\Factory\Response;

use Lav45\MockServer\Domain\Factory\Response\Url;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class UrlTest extends TestCase
{
    #[DataProvider('createUrlDataProvider')]
    public function testCreateUrl(string $url, array $get, string $expected): void
    {
        $newUrl = (new Url($url))->withQuery($get)->create()->value;

        $this->assertEquals($expected, $newUrl);
    }

    public static function createUrlDataProvider(): array
    {
        return [
            ['http://localhost', ['id' => 100], 'http://localhost?id=100'],
            ['http://localhost?id=50', ['id' => 100], 'http://localhost?id=50'],
            ['http://localhost?id=50', ['type' => 'test'], 'http://localhost?id=50&type=test'],
        ];
    }
}
