<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Parser;

use Lav45\MockServer\Parser\EnvParser;
use Lav45\MockServer\Parser\InlineParser;
use PHPUnit\Framework\TestCase;

final class EnvParserTest extends TestCase
{
    private EnvParser $parser;

    protected function setUp(): void
    {
        $inlineParser = new class implements InlineParser {
            public function replace(mixed $data): mixed
            {
                return $data;
            }
        };
        $this->parser = new EnvParser($inlineParser);
    }

    public function testParse(): void
    {
        putenv('URL=http://test.com');
        $result = $this->parser->replace('{{env.URL}}');
        $this->assertEquals('http://test.com', $result);

        $result = $this->parser->replace('{{env.DEFAULT}}');
        $this->assertEquals('{{env.DEFAULT}}', $result);

        $result = $this->parser->replace(0);
        $this->assertEquals(0, $result);
    }
}