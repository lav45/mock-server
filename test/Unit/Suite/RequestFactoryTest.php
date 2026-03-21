<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite;

use Amp\Http\Client\Response as AmpHttpResponse;
use Amp\Http\Server\Driver\Client as HttpDriverClient;
use Amp\Http\Server\Request as HttpRequest;
use Amp\Http\Server\RequestHandler;
use Amp\Socket\SocketAddress;
use Amp\Socket\TlsInfo;
use Faker\Factory as FakerFactory;
use Lav45\MockServer\Infrastructure\HttpClient\HttpClientInterface;
use Lav45\MockServer\Infrastructure\Parser\ParserFactory;
use Lav45\MockServer\RequestFactory;
use League\Uri\Http;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class FakeHttpClient implements HttpClientInterface
{
    public function request(
        string      $uri,
        string      $method = 'GET',
        string|null $body = null,
        array|null  $headers = null,
        string|null $logLabel = null,
    ): AmpHttpResponse {
        throw new \BadMethodCallException('The request method should not be called in this test.');
    }
}

final class FakeHttpDriverClient implements HttpDriverClient
{
    public function getId(): int
    {
        return 1;
    }

    public function getLocalAddress(): SocketAddress
    {
        throw new \BadMethodCallException('Not implemented in FakeHttpDriverClient');
    }

    public function getRemoteAddress(): SocketAddress
    {
        throw new \BadMethodCallException('Not implemented in FakeHttpDriverClient');
    }

    public function getTlsInfo(): TlsInfo|null
    {
        return null;
    }

    public function isClosed(): bool
    {
        return false;
    }

    public function close(): void {}

    public function onClose(\Closure $onClose): void {}
}

final class RequestFactoryTest extends TestCase
{
    private RequestFactory $factory;

    protected function setUp(): void
    {
        $parser = new ParserFactory(FakerFactory::create())->create();
        $httpClient = new FakeHttpClient();
        $logger = new NullLogger();

        $this->factory = new RequestFactory($parser, $httpClient, $logger);
    }

    public function testCreatedHandlerProcessesRequestCorrectly(): void
    {
        $mockConfig = [
            'request' => [
                'url' => '/api/test',
                'method' => 'GET',
            ],
            'response' => [
                'type' => 'content',
                'status' => 202,
                'text' => 'Success response body',
                'headers' => [
                    'X-Custom-Header' => 'TestValue',
                ],
            ],
        ];

        $handler = $this->factory->create($mockConfig);

        $this->assertInstanceOf(RequestHandler::class, $handler);

        $clientFake = new FakeHttpDriverClient();
        $uriReal = Http::new('http://localhost/api/test');

        $request = new HttpRequest($clientFake, 'GET', $uriReal);

        $request->setAttribute('urlParams', []);

        $response = $handler->handleRequest($request);

        $this->assertSame(202, $response->getStatus());
        $this->assertSame('Success response body', $response->getBody()->read());

        $this->assertTrue($response->hasHeader('X-Custom-Header'));
        $this->assertSame('TestValue', $response->getHeader('X-Custom-Header'));
    }
}
