<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Domain\ValueObject\Response;

use Lav45\MockServer\Domain\Model\Response\Delay;
use PHPUnit\Framework\TestCase;

final class DelayTest extends TestCase
{
    public function testException(): void
    {
        $this->expectException(\AssertionError::class);
        new Delay(-1);
    }
}
