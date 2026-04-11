<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Router\MockFactory;

use Lav45\MockServer\Parser\VariableParser;
use Lav45\MockServer\Router\MockFactory\AttributeBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AttributeBuilderTest extends TestCase
{
    #[DataProvider('createUrlDataProvider')]
    public function testCreateUrl(string $url, array $get, string $expected): void
    {
        $parser = new class implements VariableParser {
            public function replace(mixed $data): mixed
            {
                return $data;
            }

            public function withData(array $data): VariableParser
            {
                return $this;
            }
        };

        $newUrl = new AttributeBuilder($parser, ['url' => $url])->createUrl($get)->value;

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
