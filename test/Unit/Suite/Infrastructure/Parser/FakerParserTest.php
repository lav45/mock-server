<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Infrastructure\Parser;

use Faker\Factory;
use Lav45\MockServer\Infrastructure\Parser\FakerParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FakerParserTest extends TestCase
{
    private FakerParser $parser;

    protected function setUp(): void
    {
        $this->parser = new FakerParser(Factory::create('en_US'));
    }

    #[DataProvider('parseDataProvider')]
    public function testParse(string $str, string $pattern): void
    {
        $result = $this->parser->replace($str);
        $this->assertMatchesRegularExpression($pattern, (string)$result);
    }

    public static function parseDataProvider(): array
    {
        return [
            ["faker.md5", '~^faker\.md5$~'],
            ["{{faker.md5}}", '~^[a-f0-9]{32}$~'],
            ["{{ faker.md5 }}", '~^[a-f0-9]{32}$~'],
            ["{{faker.uuid}}", '~^[\da-f]{8}-[\da-f]{4}-[\da-f]{4}-[\da-f]{4}-[\da-f]{12}$~'],
            ["{{faker.iban('LT')}}", '~^LT\d+$~'],
            ["{{faker.iban('GB', 'SPPV')}}", '~^GB\d*SPPV\d+$~'],
            ["{{faker.bothify('########')}}", '~^\d{8}$~'],
            ["{{faker.bothify('##-##-##')}}", '~^\d{2}-\d{2}-\d{2}$~'],
            ["{{faker.bothify('?#?#?#?#?#')}}", '~^\w{1}\d{1}\w{1}\d{1}\w{1}\d{1}\w{1}\d{1}\w{1}\d{1}$~'],
            ["{{faker.numerify('############')}}", '~^\d{12}$~'],
            ["{{faker.regexify('[A-Z0-9]{14}')}}", '~^[A-Z0-9]{14}$~'],
            ["{{faker.dateTimeBetween('-1 week', '-1 hour').format('Y-m-d')}}", '~^\d{4}-\d{2}-\d{2}$~'],
            ["{{faker.dateTimeBetween('-1 week', '-1 hour').getTimestamp()}}", '~^\d+$~'],
            ["{{faker.currencyCode}}", '~^[A-Z]{3}$~'],
            ["{{faker.email}}", '~^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$~'],
            ["{faker.email} - {faker.md5}", '~^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4} - [a-f0-9]{32}$~'],
        ];
    }

    public function testRecursiveParseData(): void
    {
        $result = $this->parser->replace([
            'id' => "{{faker.uuid}}",
            'data' => [
                'email' => "{{faker.email}}",
                'items' => [
                    "{{faker.md5}}",
                    "{{faker.md5}}",
                    "{{faker.md5}}",
                ],
            ],
        ]);

        $this->assertMatchesRegularExpression('~^[\da-f]{8}-[\da-f]{4}-[\da-f]{4}-[\da-f]{4}-[\da-f]{12}$~', $result['id']);
        $this->assertMatchesRegularExpression('~^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,4}$~', $result['data']['email']);

        foreach ($result['data']['items'] as $item) {
            $this->assertMatchesRegularExpression('~^[a-f0-9]{32}$~', $item);
        }
    }

    public function testException(): void
    {
        $this->expectException(\ArgumentCountError::class);
        $this->parser->replace("{{faker.dateTimeBetween('-1 week', '-1 hour').format()}}");
    }

    public function testException2(): void
    {
        $this->expectException(\JsonException::class);
        $this->parser->replace("{{faker.dateTimeBetween('-1 week', '-1 hour').format(aaaa)}}");
    }
}
