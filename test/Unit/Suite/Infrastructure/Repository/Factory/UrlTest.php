<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Infrastructure\Repository\Factory;

use Lav45\MockServer\Infrastructure\Parser\DataParser;
use Lav45\MockServer\Infrastructure\Repository\Handler\AttributeFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class UrlTest extends TestCase
{
    #[DataProvider('createUrlDataProvider')]
    public function testCreateUrl(string $url, array $get, string $expected): void
    {
        $parser = new class implements DataParser {
            public function replace(mixed $data): mixed
            {
                return $data;
            }

            public function withData(array $data): DataParser
            {
                return $this;
            }
        };

        $newUrl = new AttributeFactory($parser, ['url' => $url])->createUrl($get)->value;

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
