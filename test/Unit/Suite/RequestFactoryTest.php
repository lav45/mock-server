<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite;

use Amp\Http\Client\Response as AmpHttpResponse;
use Amp\Http\Server\Driver\Client as HttpDriverClient;
use Amp\Http\Server\Request as HttpRequest;
use Amp\Socket\SocketAddress;
use Amp\Socket\TlsInfo;
use Faker\Factory as FakerFactory;
use Lav45\MockServer\Infrastructure\HttpClient\HttpClientInterface;
use Lav45\MockServer\Infrastructure\Parser\ParserFactory;
use Lav45\MockServer\Presenter\Handler\Request as RequestHandler;
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
        // В рамках данного теста мы не ожидаем вызовов к внешним ресурсам
        throw new \BadMethodCallException('Метод request не должен вызываться в данном тесте.');
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
        throw new \BadMethodCallException('Не реализовано в FakeHttpDriverClient');
    }

    public function getRemoteAddress(): SocketAddress
    {
        throw new \BadMethodCallException('Не реализовано в FakeHttpDriverClient');
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
        $parser = new ParserFactory(FakerFactory::create());
        $httpClient = new FakeHttpClient();
        $logger = new NullLogger();

        $this->factory = new RequestFactory($parser, $httpClient, $logger);
    }

    public function testCreateReturnsRequestHandlerInstance(): void
    {
        $handler = $this->factory->create([]);

        $this->assertInstanceOf(RequestHandler::class, $handler);
    }

    public function testCreatedHandlerProcessesRequestCorrectly(): void
    {
        // 1. Подготавливаем конфигурацию конкретного мока
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

        // 2. Создаем обработчик через фабрику
        $handler = $this->factory->create($mockConfig);

        // 3. Создаем фейковый HTTP-запрос (Amp Http Server Request)
        // Используем Fake-объект для драйвера и реальный Value Object для URI из league/uri
        $clientFake = new FakeHttpDriverClient();
        $uriReal = Http::new('http://localhost/api/test');

        $request = new HttpRequest($clientFake, 'GET', $uriReal);

        // Presenter\Handler\Request ожидает атрибут 'urlParams',
        // который в реальности устанавливается в классе Reactor из FastRoute
        $request->setAttribute('urlParams', []);

        // 4. Выполняем запрос через созданный обработчик
        $response = $handler->handleRequest($request);

        // 5. Проверяем, что ответ сформирован корректно.
        $this->assertSame(202, $response->getStatus());
        $this->assertSame('Success response body', $response->getBody()->read());

        $this->assertTrue($response->hasHeader('X-Custom-Header'));
        $this->assertSame('TestValue', $response->getHeader('X-Custom-Header'));
    }
}
