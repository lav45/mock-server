<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Domain\Response;

use Lav45\MockServer\Domain\ValueObject\Body;
use PHPUnit\Framework\TestCase;

final class BodyTest extends TestCase
{
    public function testReadString(): void
    {
        $text = 'content';
        $result = Body::new($text)->stream->read();
        $this->assertEquals($text, $result);
    }

    public function testReadEncodesArrayToJson(): void
    {
        $data = ['status' => true];
        $text = '{"status":true}';
        $result = Body::new($data)->stream->read();
        $this->assertEquals($text, $result);
    }

    public function testReadUnescapesSlashesAndUnicode(): void
    {
        $data = ['url' => 'https://example.com/path', 'name' => 'Привет'];
        $text = '{"url":"https://example.com/path","name":"Привет"}';
        $result = Body::new($data)->stream->read();
        $this->assertEquals($text, $result);
    }
}
