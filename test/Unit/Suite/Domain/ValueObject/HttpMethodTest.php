<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Domain\ValueObject;

use Lav45\MockServer\Domain\ValueObject\HttpMethod;
use PHPUnit\Framework\TestCase;

final class HttpMethodTest extends TestCase
{
    public function testValidMethod(): void
    {
        $this->assertSame('GET', new HttpMethod('GET')->value);
    }

    public function testThrowsForEmptyMethod(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new HttpMethod('');
    }

    public function testThrowsForLowercaseMethod(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new HttpMethod('get');
    }

    public function testThrowsForMethodWithLeadingDigit(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new HttpMethod('1GET');
    }

    public function testThrowsForMethodWithTrailingDigit(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new HttpMethod('GET1');
    }
}
