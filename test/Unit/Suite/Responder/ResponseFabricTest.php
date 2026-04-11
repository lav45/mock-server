<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Responder;

use Lav45\MockServer\Domain\Mock\Response;
use Lav45\MockServer\Responder\ContentResponder;
use Lav45\MockServer\Responder\ResponderInterface;
use Lav45\MockServer\Responder\ResponseFabric;
use PHPUnit\Framework\TestCase;

final class ResponseFabricTest extends TestCase
{
    public function testInvalidArgumentException(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $data = new class implements Response {
            public function delay(): float
            {
                return 0.0;
            }
        };

        new ResponseFabric()->create($data);
    }

    public function testSuccess(): void
    {
        $data = new class implements Response {
            public function delay(): float
            {
                return 0.0;
            }
        };

        $responseFabric = new ResponseFabric([
            \get_class($data) => new ContentResponder(),
        ]);

        $responder = $responseFabric->create($data);

        $this->assertInstanceOf(ResponderInterface::class, $responder);
    }
}
