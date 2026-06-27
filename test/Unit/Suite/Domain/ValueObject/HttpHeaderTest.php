<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Domain\ValueObject;

use Lav45\MockServer\Domain\ValueObject\HttpHeader;
use PHPUnit\Framework\TestCase;

final class HttpHeaderTest extends TestCase
{
    public function testValidHeader(): void
    {
        $header = new HttpHeader('Content-Type', 'application/json');
        $this->assertSame('Content-Type', $header->name);
        $this->assertSame('application/json', $header->value);
    }

    public function testThrowsForInvalidNameWithMessage(): void
    {
        try {
            new HttpHeader('bad name', 'value');
            $this->fail('Expected InvalidArgumentException');
        } catch (\InvalidArgumentException $e) {
            $this->assertSame('Invalid header name: "bad name"', $e->getMessage());
        }
    }

    public function testThrowsForInvalidValueWithMessage(): void
    {
        try {
            new HttpHeader('X-Custom', "bad\r\nvalue");
            $this->fail('Expected InvalidArgumentException');
        } catch (\InvalidArgumentException $e) {
            $this->assertSame("Invalid header value: \"bad\r\nvalue\"", $e->getMessage());
        }
    }
}
