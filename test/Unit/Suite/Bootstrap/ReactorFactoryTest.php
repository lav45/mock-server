<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Bootstrap;

use Lav45\MockServer\Bootstrap\ReactorFactory;
use Lav45\MockServer\Domain\WebHooks;
use Lav45\MockServer\Engine\Http\RequestHandler;
use Lav45\MockServer\Engine\WebHookQueue;
use Lav45\MockServer\Extension\Collection\CollectionExtension;
use Lav45\MockServer\Extension\Condition\ConditionExtension;
use Lav45\MockServer\Extension\Content\ContentExtension;
use Lav45\MockServer\Extension\Cors\CorsExtension;
use Lav45\MockServer\Extension\Direct\DirectExtension;
use Lav45\MockServer\Extension\Extension;
use Lav45\MockServer\Extension\Proxy\ProxyExtension;
use Lav45\MockServer\Extension\Throttling\ThrottlingExtension;
use Lav45\MockServer\Extension\WebHook\WebHookExtension;
use Lav45\MockServer\Test\Unit\Components\FakeHttpClient;
use Lav45\MockServer\Test\Unit\Components\FakeLogger;
use Lav45\MockServer\Test\Unit\Components\FakeServerRequest;
use Lav45\MockServer\Test\Unit\Components\ThrowingExtension;
use PHPUnit\Framework\TestCase;

final class ReactorFactoryTest extends TestCase
{
    private const string SCHEMA_PATH = __DIR__ . '/../../../../schema/mock.schema.json';

    private string $mocksPath;

    /** @var list<string> */
    private array $schemaFiles = [];

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
        foreach ($this->schemaFiles as $schemaFile) {
            \unlink($schemaFile);
        }
    }

    private function writeSchema(array $schema): string
    {
        $path = \sys_get_temp_dir() . '/reactor-schema-' . \uniqid('', true) . '.json';
        \file_put_contents($path, \json_encode($schema, JSON_THROW_ON_ERROR));
        $this->schemaFiles[] = $path;

        return $path;
    }

    /**
     * @param list<Extension> $extensions
     */
    private function createReactor(
        array $extensions = [new Extension(ContentExtension::class)],
        string|null $schema = null,
        array $env = [],
    ): RequestHandler {
        $httpClient = new FakeHttpClient();

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
            extensions: $extensions,
            schema: $schema,
            env: $env,
        )->create();
    }

    public function testHandlesMatchedRoute(): void
    {
        $reactor = $this->createReactor();

        $response = $reactor->handleRequest(new FakeServerRequest('GET', 'https://localhost/ping'));

        $this->assertSame(201, $response->getStatus());
        $this->assertStringContainsString('pong', $response->getBody()->stream->read());
    }

    public function testReturnsNotFoundForUnknownRoute(): void
    {
        $reactor = $this->createReactor();

        $response = $reactor->handleRequest(new FakeServerRequest('GET', 'https://localhost/missing'));

        $this->assertSame(404, $response->getStatus());
    }

    public function testBuildsPipelineWithAllKnownExtensions(): void
    {
        $reactor = $this->createReactor([
            new Extension(CorsExtension::class, ['allow_origin' => '*']),
            new Extension(ConditionExtension::class),
            new Extension(DirectExtension::class),
            new Extension(WebHookExtension::class),
            new Extension(ThrottlingExtension::class),
            new Extension(ContentExtension::class),
            new Extension(ProxyExtension::class),
            new Extension(CollectionExtension::class),
        ]);

        $response = $reactor->handleRequest(new FakeServerRequest('GET', 'https://localhost/ping'));

        $this->assertSame(201, $response->getStatus());
        $this->assertStringContainsString('pong', $response->getBody()->stream->read());
    }

    public function testThrowsForUnknownExtensionClass(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('Invalid extension: bogus');

        $this->createReactor([new Extension('bogus')]);
    }

    public function testThrowsForClassNotImplementingProvider(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('Invalid extension: ' . \stdClass::class);

        $this->createReactor([new Extension(\stdClass::class)]);
    }

    public function testExtensionConfigMatchingSchemaIsAccepted(): void
    {
        $schema = $this->writeSchema([
            'type' => 'object',
            'properties' => ['allowOrigin' => ['type' => 'string']],
            'required' => ['allowOrigin'],
        ]);

        $reactor = $this->createReactor([
            new Extension(CorsExtension::class, ['allowOrigin' => '*'], $schema),
            new Extension(ContentExtension::class),
        ]);

        $response = $reactor->handleRequest(new FakeServerRequest('GET', 'https://localhost/ping'));

        $this->assertSame(201, $response->getStatus());
    }

    public function testThrowsWhenExtensionConfigViolatesSchema(): void
    {
        $schema = $this->writeSchema([
            'type' => 'object',
            'properties' => ['allowOrigin' => ['type' => 'string']],
            'required' => ['allowOrigin'],
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains(CorsExtension::class . ' config is invalid');

        $this->createReactor([
            new Extension(CorsExtension::class, ['allowOrigin' => 123], $schema),
        ]);
    }

    public function testThrowsWhenExtensionFailsToInitialize(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains(ThrowingExtension::class . ' failed to initialize: boom');

        $this->createReactor([new Extension(ThrowingExtension::class)]);
    }

    public function testWithoutSchemaAllowsCustomMockKeys(): void
    {
        $this->writeMock(['audit' => ['actor' => 'system']]);

        $reactor = $this->createReactor();
        $response = $reactor->handleRequest(new FakeServerRequest('GET', 'https://localhost/ping'));

        $this->assertSame(201, $response->getStatus());
    }

    public function testSchemaRejectsCustomMockKeys(): void
    {
        $this->writeMock(['audit' => ['actor' => 'system']]);

        $reactor = $this->createReactor(schema: self::SCHEMA_PATH);
        $response = $reactor->handleRequest(new FakeServerRequest('GET', 'https://localhost/ping'));

        $this->assertSame(404, $response->getStatus());
    }

    public function testSchemaAcceptsValidMock(): void
    {
        $reactor = $this->createReactor(schema: self::SCHEMA_PATH);
        $response = $reactor->handleRequest(new FakeServerRequest('GET', 'https://localhost/ping'));

        $this->assertSame(201, $response->getStatus());
        $this->assertStringContainsString('pong', $response->getBody()->stream->read());
    }

    public function testGlobalEnvIsAvailableInMocks(): void
    {
        \file_put_contents(
            $this->mocksPath . '/mock.json',
            \json_encode([
                [
                    'version' => 8,
                    'request' => ['method' => 'GET', 'path' => '/ping'],
                    'response' => ['type' => 'content', 'status' => 200, 'body' => ['domain' => '{{env.DOMAIN}}']],
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $reactor = $this->createReactor(env: ['DOMAIN' => 'api.server.com']);
        $response = $reactor->handleRequest(new FakeServerRequest('GET', 'https://localhost/ping'));

        $this->assertSame(200, $response->getStatus());
        $this->assertStringContainsString('api.server.com', $response->getBody()->stream->read());
    }

    public function testGlobalEnvIsMergedWithMockEnv(): void
    {
        \file_put_contents(
            $this->mocksPath . '/mock.json',
            \json_encode([
                [
                    'version' => 8,
                    'request' => ['method' => 'GET', 'path' => '/ping'],
                    'env' => ['token' => 'secret'],
                    'response' => [
                        'type' => 'content',
                        'status' => 200,
                        'body' => [
                            'domain' => '{{env.DOMAIN}}',
                            'token' => '{{env.token}}',
                        ],
                    ],
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $reactor = $this->createReactor(env: ['DOMAIN' => 'api.server.com']);
        $response = $reactor->handleRequest(new FakeServerRequest('GET', 'https://localhost/ping'));

        $this->assertSame(200, $response->getStatus());
        $body = $response->getBody()->stream->read();
        $this->assertStringContainsString('api.server.com', $body);
        $this->assertStringContainsString('secret', $body);
    }

    public function testMockEnvOverridesGlobalEnv(): void
    {
        \file_put_contents(
            $this->mocksPath . '/mock.json',
            \json_encode([
                [
                    'version' => 8,
                    'request' => ['method' => 'GET', 'path' => '/ping'],
                    'env' => ['DOMAIN' => 'mock.local'],
                    'response' => ['type' => 'content', 'status' => 200, 'body' => ['domain' => '{{env.DOMAIN}}']],
                ],
            ], JSON_THROW_ON_ERROR),
        );

        $reactor = $this->createReactor(env: ['DOMAIN' => 'api.server.com']);
        $response = $reactor->handleRequest(new FakeServerRequest('GET', 'https://localhost/ping'));

        $this->assertSame(200, $response->getStatus());
        $this->assertSame('{"domain":"mock.local"}', $response->getBody()->stream->read());
    }

    private function writeMock(array $extra): void
    {
        \file_put_contents(
            $this->mocksPath . '/mock.json',
            \json_encode([
                [
                    'version' => 8,
                    'request' => ['method' => 'GET', 'path' => '/ping'],
                    ...$extra,
                    'response' => ['type' => 'content', 'status' => 201, 'body' => ['message' => 'pong']],
                ],
            ], JSON_THROW_ON_ERROR),
        );
    }
}
