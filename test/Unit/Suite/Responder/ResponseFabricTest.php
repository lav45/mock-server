<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Responder;

use Amp\Http\Server\Response as HttpResponse;
use Lav45\MockServer\Domain\Mock\Response;
use Lav45\MockServer\Domain\Mock\Response\ContentResponse;
use Lav45\MockServer\Responder\ContentResponder;
use Lav45\MockServer\Responder\ResponseFactory;
use PHPUnit\Framework\TestCase;

final class ResponseFabricTest extends TestCase
{
    public function testInvalidArgumentException(): void
    {
        $data = new class implements Response {
            public function delay(): float
            {
                return 0.0;
            }
        };

        $this->expectException(\InvalidArgumentException::class);

        new ResponseFactory()->create($data);
    }

    public function testSuccess(): void
    {
        $responseFactory = new ResponseFactory([
            ContentResponse::class => new ContentResponder(),
        ]);

        $response = new ContentResponse(
            delay: new Response\Delay(0.0),
            status: new Response\HttpStatus(200),
            headers: new Response\HttpHeaders(),
            body: Response\Body::fromText(''),
        );

        $responder = $responseFactory->create($response);

        $this->assertInstanceOf(HttpResponse::class, $responder);
    }

    public function testRuntimeException(): void
    {
        $responseFactory = new ResponseFactory([
            FakeResponse::class => new ContentResponder(),
        ]);

        $response = new FakeResponse();

        $this->expectException(\RuntimeException::class);

        $responseFactory->create($response);
    }
}

final readonly class FakeResponse implements Response
{
    public function delay(): float
    {
        return 0.0;
    }
}
