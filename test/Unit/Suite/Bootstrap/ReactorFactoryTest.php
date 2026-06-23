<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Bootstrap;

use Lav45\MockServer\Bootstrap\ReactorFactory;
use Lav45\MockServer\Domain\WebHooks;
use Lav45\MockServer\Engine\Http\ClientResponse;
use Lav45\MockServer\Engine\Http\RequestHandler;
use Lav45\MockServer\Engine\HttpClient;
use Lav45\MockServer\Engine\WebHookQueue;
use Lav45\MockServer\Test\Unit\Components\FakeLogger;
use Lav45\MockServer\Test\Unit\Components\FakeServerRequest;
use PHPUnit\Framework\TestCase;

final class ReactorFactoryTest extends TestCase
{
    private string $mocksPath;

    protected function setUp(): void
    {
        $this->mocksPath = \sys_get_temp_dir() . '/reactor-factory-' . \uniqid('', true);
        \mkdir($this->mocksPath);
        \file_put_contents(
            $this->mocksPath . '/mock.json',
            \json_encode([
                [
                    'version' => 8,
                    'request' => ['method' => 'GET', 'path' => '/ping'],
                    'response' => ['type' => 'content', 'status' => 201, 'body' => ['message' => 'pong']],
                ],
            ], JSON_THROW_ON_ERROR),
        );
    }

    protected function tearDown(): void
    {
        \unlink($this->mocksPath . '/mock.json');
        \rmdir($this->mocksPath);
    }

    private function createReactor(): RequestHandler
    {
        $httpClient = new class implements HttpClient {
            public function withLabel(string $label): HttpClient
            {
                return $this;
            }

            public function request(
                string $uri,
                string $method = 'GET',
                array|null $headers = null,
                string|null $body = null,
            ): ClientResponse {
                return new ClientResponse(200, [], '');
            }
        };

        $webHookQueue = new class implements WebHookQueue {
            public function push(WebHooks $webHooks): void {}
        };

        return new ReactorFactory(
            mocksPath: $this->mocksPath,
            locale: 'en_US',
            filterHeaders: [],
            httpClient: $httpClient,
            webHookQueue: $webHookQueue,
            logger: new FakeLogger(),
        )->create();
    }

    public function testHandlesMatchedRoute(): void
    {
        $reactor = $this->createReactor();

        $response = $reactor->handleRequest(new FakeServerRequest('GET', 'https://localhost/ping'));

        $this->assertSame(201, $response->getStatus());
        $this->assertStringContainsString('pong', $response->getBody());
    }

    public function testReturnsNotFoundForUnknownRoute(): void
    {
        $reactor = $this->createReactor();

        $response = $reactor->handleRequest(new FakeServerRequest('GET', 'https://localhost/missing'));

        $this->assertSame(404, $response->getStatus());
    }
}
