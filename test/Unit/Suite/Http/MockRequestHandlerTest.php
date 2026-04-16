<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Http;

use Amp\Http\Server\Request as HttpRequest;
use Amp\Http\Server\RequestHandler;
use Faker\Factory as FakerFactory;
use Lav45\MockServer\Domain\Mock\Response\ContentResponse as ContentEntity;
use Lav45\MockServer\Http\MockRequestHandler;
use Lav45\MockServer\Http\RequestDataFactory;
use Lav45\MockServer\Http\RequestFactory;
use Lav45\MockServer\Parser\ParserFactory;
use Lav45\MockServer\Responder\ContentResponder;
use Lav45\MockServer\Responder\DelayHandler;
use Lav45\MockServer\Responder\ResponseFabric;
use Lav45\MockServer\Responder\WebHookHandlerInterface;
use Lav45\MockServer\Router\MockFactory;
use Lav45\MockServer\Router\MockFactory\ContentFactory;
use Lav45\MockServer\Router\MockFactory\RequestParserContext;
use Lav45\MockServer\Router\MockFactory\ResponseFactoryResolver;
use Lav45\MockServer\Router\MockFactory\WebHooksFactory;
use Lav45\MockServer\Test\Unit\Components\FakeHttpDriverClient;
use League\Uri\Http;
use PHPUnit\Framework\TestCase;

final class MockRequestHandlerTest extends TestCase
{
    private RequestFactory $factory;

    protected function setUp(): void
    {
        $parser = new ParserFactory(FakerFactory::create())->create();
        $webHookHandler = new class implements WebHookHandlerInterface {
            public function send(iterable $webHooks): void {}
        };
        $responseFabric = new ResponseFabric([
            ContentEntity::class => new ContentResponder(),
        ]);
        $requestDataFactory = new RequestDataFactory();
        $delayHandler = new DelayHandler();
        $mockFactory = new MockFactory(
            parserContext: new RequestParserContext($parser),
            webHooksFactory: new WebHooksFactory(),
            responseFactoryResolver: new ResponseFactoryResolver([
                ContentFactory::TYPE => new ContentFactory(),
            ]),
        );

        $this->factory = new MockRequestHandler(
            webHookHandler: $webHookHandler,
            responseFabric: $responseFabric,
            mockFactory: $mockFactory,
            requestDataFactory: $requestDataFactory,
            delayHandler: $delayHandler,
        );
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

        $handler = $this->factory->withData($mockConfig);

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
