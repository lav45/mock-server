<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Presenter\Service;

use Amp\Http\Server\Request;
use Lav45\MockServer\Presenter\Service\Request as RequestService;
use PHPUnit\Framework\TestCase;

final class RequestTest extends TestCase
{
    public function testCallException(): void
    {
        $request = (new \ReflectionClass(Request::class))->newInstanceWithoutConstructor();
        $testClass = new RequestService($request);

        $this->expectException(\RuntimeException::class);
        $testClass->aaa();
    }
}
