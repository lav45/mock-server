<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Domain\Mock\Response;

use Lav45\MockServer\Domain\Mock\Response\Delay;
use PHPUnit\Framework\TestCase;

final class DelayTest extends TestCase
{
    public function testException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Delay(-1);
    }
}
