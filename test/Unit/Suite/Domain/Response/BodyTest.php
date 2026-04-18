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
}
