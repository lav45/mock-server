<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Infrastructure\Parser;

use Lav45\MockServer\Infrastructure\Parser\DateParse;
use Lav45\MockServer\Infrastructure\Parser\InlineParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DateParserTest extends TestCase
{
    private DateParse $parser;

    protected function setUp(): void
    {
        $inlineParser = new class implements InlineParser {
            public function replace(mixed $data): mixed
            {
                return $data;
            }
        };
        $this->parser = new DateParse($inlineParser);
    }

    #[DataProvider('parseDataProvider')]
    public function testParse(string $input, string $pattern): void
    {
        $result = $this->parser->replace($input);
        $this->assertMatchesRegularExpression($pattern, (string)$result);
    }

    public static function parseDataProvider(): array
    {
        return [
            [
                '{{date.getTimestamp()}}',
                '~^\d+$~',
            ],
            [
                "{{date.format('Y-m-d\TH:i:s.u\Z')}}",
                '~^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{6}Z$~',
            ],
            [
                "{{date.format('Y-m-d')}}",
                '~^\d{4}-\d{2}-\d{2}$~',
            ],
            [
                '{date.getTimestamp()}',
                '~^\d+$~',
            ],
            [
                "{date.format('Y-m-d\TH:i:s.u\Z')}",
                '~^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{6}Z$~',
            ],
            [
                "{date.format('Y-m-d')}",
                '~^\d{4}-\d{2}-\d{2}$~',
            ],
            [
                "{{faker.md5}}",
                "{{faker.md5}}",
            ],
        ];
    }
}
