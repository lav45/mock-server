<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Infrastructure\Wrapper;

use Amp\Http\Server\Request;
use Lav45\MockServer\Infrastructure\Wrapper\Request as RequestWrapper;
use PHPUnit\Framework\TestCase;

final class RequestTest extends TestCase
{
    public function testCallException(): void
    {
        $request = (new \ReflectionClass(Request::class))->newInstanceWithoutConstructor();
        $testClass = new RequestWrapper($request);

        $this->expectException(\RuntimeException::class);
        $testClass->aaa();
    }
}
