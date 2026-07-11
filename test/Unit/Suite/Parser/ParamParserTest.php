<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Parser;

use Lav45\MockServer\Parser\InlineParser;
use Lav45\MockServer\Parser\ParamParser;
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
        $result = $this->parser->withData($data)->replace($input);
        $this->assertEquals($expected, $result);
    }

    public static function parseDataProvider(): array
    {
        return [
            [
                [
                    'response' => [
                        'items' => static fn() => [],
                    ],
                ],
                '{{response.items}}',
                [],
            ],
            [
                [
                    'response' => [
                        'items' => [],
                    ],
                ],
                '{{response.items}}',
                [],
            ],
            [
                [
                    'response' => [
                        'items' => [1, 2, 3],
                    ],
                ],
                '{{response.items}}',
                [1, 2, 3],
            ],
            [
                ['data' => 123],
                '{{data}}',
                123,
            ],
            [
                ['data' => 123],
                '{data}',
                '123',
            ],
            [
                [
                    'data' => 123,
                    'items' => [1, 2, 3],
                ],
                '{{items}}',
                [1, 2, 3],
            ],
        ];
    }

    public function testInlineBoolIsExportedToString(): void
    {
        $parser = $this->parser->withData(['flag' => true, 'off' => false]);

        $this->assertSame('true', $parser->replace('{flag}'));
        $this->assertSame('false', $parser->replace('{off}'));
        $this->assertSame('has next: true', $parser->replace('has next: {flag}'));
    }

    public function testDoubleBraceBoolKeepsType(): void
    {
        $parser = $this->parser->withData(['flag' => true]);

        $this->assertTrue($parser->replace('{{flag}}'));
    }

    public function testWithDataMergesRecursively(): void
    {
        $result = $this->parser
            ->withData(['a' => 1])
            ->withData(['b' => 2])
            ->replace('{{a}}');
        $this->assertSame(1, $result);
    }
}
