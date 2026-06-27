<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Engine\Http;

use Lav45\MockServer\Engine\Http\ServerResponse;
use PHPUnit\Framework\TestCase;

final class ServerResponseTest extends TestCase
{
    public function testDefaultStatusIsOk(): void
    {
        $this->assertSame(200, new ServerResponse()->getStatus());
    }
}
