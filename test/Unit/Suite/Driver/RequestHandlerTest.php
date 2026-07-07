<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Driver;

use Amp\ByteStream\ReadableBuffer;
use Amp\Http\Server\Request as AmpRequest;
use Amp\Http\Server\RequestBody;
use Lav45\MockServer\Domain\ValueObject\Body;
use Lav45\MockServer\Driver\AmpStream;
use Lav45\MockServer\Driver\RequestHandler;
use Lav45\MockServer\Engine\Http\RequestHandler as EngineRequestHandler;
use Lav45\MockServer\Engine\Http\ServerRequest;
use Lav45\MockServer\Engine\Http\ServerResponse;
use Lav45\MockServer\Test\Unit\Components\FakeHttpDriverClient;
use League\Uri\Http;
use PHPUnit\Framework\TestCase;

use function Amp\ByteStream\buffer;

final class RequestHandlerTest extends TestCase
{
    private function createAmpRequest(string $method = 'GET', string $url = 'https://localhost/'): AmpRequest
    {
        return new AmpRequest(new FakeHttpDriverClient(), $method, Http::new($url), [], new RequestBody(''));
    }

    public function testHandleRequestConvertsEngineResponseToAmpResponse(): void
    {
        $handler = new class implements EngineRequestHandler {
            public function handleRequest(ServerRequest $request): ServerResponse
            {
                return new ServerResponse(201, ['x-token' => 'secret'], Body::new('created'));
            }
        };

        $ampResponse = new RequestHandler($handler)->handleRequest($this->createAmpRequest());

        $this->assertSame(201, $ampResponse->getStatus());
        $this->assertSame('Created', $ampResponse->getReason());
        $this->assertSame('secret', $ampResponse->getHeader('x-token'));
        $this->assertSame('created', buffer($ampResponse->getBody()));
    }

    public function testHandleRequestPassesConvertedServerRequestToHandler(): void
    {
        $handler = new class implements EngineRequestHandler {
            public ServerRequest|null $capturedRequest = null;

            public function handleRequest(ServerRequest $request): ServerResponse
            {
                $this->capturedRequest = $request;
                return new ServerResponse();
            }
        };

        new RequestHandler($handler)->handleRequest(
            $this->createAmpRequest('POST', 'https://localhost/api/users'),
        );

        $this->assertSame('POST', $handler->capturedRequest->getMethod());
        $this->assertSame('/api/users', $handler->capturedRequest->getPath());
    }

    public function testHandleRequestPassesAmpStreamBodyThrough(): void
    {
        $handler = new class implements EngineRequestHandler {
            public function handleRequest(ServerRequest $request): ServerResponse
            {
                return new ServerResponse(200, [], Body::new(
                    new AmpStream(new ReadableBuffer('streamed')),
                ));
            }
        };

        $ampResponse = new RequestHandler($handler)->handleRequest($this->createAmpRequest());

        $this->assertSame('streamed', buffer($ampResponse->getBody()));
    }
}
