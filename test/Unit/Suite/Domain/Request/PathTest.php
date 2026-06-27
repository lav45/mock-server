<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Domain\Request;

use Lav45\MockServer\Domain\Request\Path;
use PHPUnit\Framework\TestCase;

final class PathTest extends TestCase
{
    public function testValidPath(): void
    {
        $this->assertSame('/users/1', new Path('/users/1')->value);
    }

    public function testThrowsForEmptyPath(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Path('');
    }

    public function testThrowsForPathWithoutLeadingSlash(): void
    {
        try {
            new Path('users');
            $this->fail('Expected InvalidArgumentException');
        } catch (\InvalidArgumentException $e) {
            $this->assertSame('Invalid path: "users"', $e->getMessage());
        }
    }
}
