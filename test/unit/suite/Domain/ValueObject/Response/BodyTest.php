<?php declare(strict_types=1);

namespace lav45\MockServer\test\unit\suite\Domain\ValueObject\Response;

use lav45\MockServer\Domain\ValueObject\Response\Body;
use PHPUnit\Framework\TestCase;

final class BodyTest extends TestCase
{
    public function testToString(): void
    {
        $text = 'content';
        $result = (string)Body::new($text);
        $this->assertEquals($text, $result);
    }
}
