<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Domain\ValueObject;

use Lav45\MockServer\Domain\ValueObject\Url;
use PHPUnit\Framework\TestCase;

final class UrlTest extends TestCase
{
    public function testValidUrl(): void
    {
        $this->assertSame('https://example.com', new Url('https://example.com')->value);
    }

    public function testThrowsForInvalidUrlWithMessage(): void
    {
        try {
            new Url('not-a-url');
            $this->fail('Expected InvalidArgumentException');
        } catch (\InvalidArgumentException $e) {
            $this->assertSame('Invalid url: "not-a-url"', $e->getMessage());
        }
    }
}
