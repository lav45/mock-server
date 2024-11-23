<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Domain\ValueObject\Response;

use Lav45\MockServer\Domain\Model\Response\Body;
use PHPUnit\Framework\TestCase;

final class BodyTest extends TestCase
{
    public function testToString(): void
    {
        $text = 'content';
        $result = Body::new($text)->toString();
        $this->assertEquals($text, $result);
    }
}
