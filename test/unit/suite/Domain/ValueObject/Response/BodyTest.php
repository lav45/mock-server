<?php declare(strict_types=1);

namespace lav45\MockServer\test\unit\suite\Domain\ValueObject\Response;

use PHPUnit\Framework\TestCase;
use lav45\MockServer\Domain\ValueObject\Response\Body;

final class BodyTest extends TestCase
{
    public function testToString(): void
    {
        $text = 'content';
        $result = (string)Body::new($text);
        $this->assertEquals($text, $result);
    }
}