<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Infrastructure\Parser;

use Lav45\MockServer\Infrastructure\Parser\InlineParser;
use Lav45\MockServer\Infrastructure\Parser\ParamParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ParamParserTest extends TestCase
{
    private ParamParser $parser;

    protected function setUp(): void
    {
        $inlineParser = new class implements InlineParser {
            public function replace(mixed $data): mixed
            {
                return $data;
            }
        };

        $this->parser = new ParamParser($inlineParser);
    }

    #[DataProvider('parseDataProvider')]
    public function testParse(array $data, mixed $input, mixed $expected): void
    {
        $parser = $this->parser->withData($data);
        $result = $parser->replace($input);
        $this->assertEquals($expected, $result);
    }

    public static function parseDataProvider(): array
    {
        return [
            [
                [
                    'response' => [
                        'items' => [],
                    ],
                ],
                '{{response.items}}',
                [],
            ],
        ];
    }
}
