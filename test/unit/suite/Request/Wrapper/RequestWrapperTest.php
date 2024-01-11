<?php declare(strict_types=1);

namespace lav45\MockServer\test\unit\suite\Request\Wrapper;

use Amp\Http\Server\Request;
use lav45\MockServer\Request\Wrapper\RequestWrapper;
use PHPUnit\Framework\TestCase;

class RequestWrapperTest extends TestCase
{
    public function testCallException(): void
    {
        $request = (new \ReflectionClass(Request::class))->newInstanceWithoutConstructor();
        $testClass = new RequestWrapper($request);

        $this->expectException(\RuntimeException::class);
        $testClass->aaa();
    }
}