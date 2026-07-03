<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Driver;

use Amp\Http\Server\Request as AmpRequest;
use Amp\Http\Server\RequestBody;
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
                return new ServerResponse(201, ['x-token' => 'secret'], 'created');
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
        $capturedRequest = null;
        $handler = new class ($capturedRequest) implements EngineRequestHandler {
            public function __construct(private mixed &$capturedRequest) {}

            public function handleRequest(ServerRequest $request): ServerResponse
            {
                $this->capturedRequest = $request;
                return new ServerResponse();
            }
        };

        new RequestHandler($handler)->handleRequest(
            $this->createAmpRequest('POST', 'https://localhost/api/users'),
        );

        $this->assertSame('POST', $capturedRequest->getMethod());
        $this->assertSame('/api/users', $capturedRequest->getPath());
    }
}
