<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Domain\Response;

use Lav45\MockServer\Domain\ValueObject\Body;
use PHPUnit\Framework\TestCase;

final class BodyTest extends TestCase
{
    public function testToString(): void
    {
        $text = 'content';
        $result = Body::new($text)->toString();
        $this->assertEquals($text, $result);

        $data = ['status' => true];
        $text = '{"status":true}';
        $result = Body::new($data)->toString();
        $this->assertEquals($text, $result);
    }

    public function testToStringUnescapesSlashesAndUnicode(): void
    {
        $data = ['url' => 'https://example.com/path', 'name' => 'Привет'];
        $text = '{"url":"https://example.com/path","name":"Привет"}';
        $result = Body::new($data)->toString();
        $this->assertEquals($text, $result);
    }

    public function testIsJsonWithArray(): void
    {
        $result = Body::new(['status' => true])->isJson();
        $this->assertTrue($result);
    }

    public function testIsJsonWithValidJsonString(): void
    {
        $result = Body::new('{"status":true}')->isJson();
        $this->assertTrue($result);
    }

    public function testIsJsonWithInvalidJsonString(): void
    {
        $result = Body::new('content')->isJson();
        $this->assertFalse($result);
    }
}
