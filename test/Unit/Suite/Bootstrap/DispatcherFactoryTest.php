<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Bootstrap;

use FastRoute\Dispatcher;
use Lav45\MockServer\Bootstrap\DispatcherFactory;
use Lav45\MockServer\Bootstrap\MockSchemaValidator;
use Lav45\MockServer\Test\Unit\Components\FakeLogger;
use PHPUnit\Framework\TestCase;

final class DispatcherFactoryTest extends TestCase
{
    private FakeLogger $logger;
    private DispatcherFactory $factory;

    protected function setUp(): void
    {
        $this->logger = new FakeLogger();
        $this->factory = new DispatcherFactory(
            migrate: static fn(array $data): array => $data,
            validator: new MockSchemaValidator(),
            logger: $this->logger,
        );
    }

    protected function tearDown(): void
    {
        $this->logger->reset();
    }

    public function testCreateWithValidRoutes(): void
    {
        $data = [
            [
                'request' => [
                    'method' => 'GET',
                    'path' => '/users',
                ],
                'response' => ['body' => ['id' => 1]],
            ],
            [
                'request' => [
                    'method' => ['POST', 'PUT'],
                    'path' => '/users',
                ],
                'response' => ['body' => ['created' => true]],
            ],
            [
                'request' => [
                    'method' => 'DELETE',
                    'path' => '/users/1',
                ],
                'response' => ['body' => ['deleted' => true]],
            ],
        ];

        $dispatcher = $this->factory->create($data);

        // Проверяем лог debug
        $debugMessages = $this->logger->getMessages('debug');
        $this->assertCount(3, $debugMessages);
        $this->assertContains('Added route: [GET] /users', $debugMessages);
        $this->assertContains('Added route: [POST,PUT] /users', $debugMessages);
        $this->assertContains('Added route: [DELETE] /users/1', $debugMessages);

        // Проверяем, что ошибок не было
        $this->assertEmpty($this->logger->getMessages('error'));

        $expends = [
            'request' => [
                'method' => 'GET',
                'path' => '/users',
            ],
            'response' => ['body' => ['id' => 1]],
        ];
        $result = $dispatcher->dispatch('GET', '/users');
        $this->assertEquals(Dispatcher::FOUND, $result[0]);
        $this->assertEquals($expends, $result[1]);

        $expends = [
            'request' => [
                'method' => ['POST', 'PUT'],
                'path' => '/users',
            ],
            'response' => ['body' => ['created' => true]],
        ];
        $result = $dispatcher->dispatch('POST', '/users');
        $this->assertEquals(Dispatcher::FOUND, $result[0]);
        $this->assertEquals($expends, $result[1]);

        $result = $dispatcher->dispatch('PUT', '/users');
        $this->assertEquals(Dispatcher::FOUND, $result[0]);
        $this->assertEquals($expends, $result[1]);

        $expends = [
            'request' => [
                'method' => 'DELETE',
                'path' => '/users/1',
            ],
            'response' => ['body' => ['deleted' => true]],
        ];
        $result = $dispatcher->dispatch('DELETE', '/users/1');
        $this->assertEquals(Dispatcher::FOUND, $result[0]);
        $this->assertEquals($expends, $result[1]);

        $result = $dispatcher->dispatch('GET', '/unknown');
        $this->assertEquals(Dispatcher::NOT_FOUND, $result[0]);
    }

    public function testAppliesMigrationBeforeRouting(): void
    {
        $migrate = static function (array $data): array {
            $data['request']['path'] = '/rewritten';
            $data['response'] = ['body' => 'migrated'];
            return $data;
        };

        $factory = new DispatcherFactory($migrate, new MockSchemaValidator(), $this->logger);
        $dispatcher = $factory->create([
            ['request' => ['method' => 'GET', 'path' => '/original'], 'response' => ['body' => 'raw']],
        ]);

        // Маршрут зарегистрирован по пути, полученному из миграции, а не исходному
        $this->assertEquals(Dispatcher::NOT_FOUND, $dispatcher->dispatch('GET', '/original')[0]);

        $result = $dispatcher->dispatch('GET', '/rewritten');
        $this->assertEquals(Dispatcher::FOUND, $result[0]);
        $this->assertSame(['body' => 'migrated'], $result[1]['response']);
    }

    public function testCreateWithInvalidUrl(): void
    {
        $data = [
            [
                'request' => [
                    'method' => 'GET',
                    'path' => '/valid',
                ],
                'response' => ['body' => 'ok'],
            ],
            [
                'request' => [
                    'method' => 'GET',
                    'path' => 'invalid',
                ],
                'response' => ['body' => 'should not be added'],
            ],
        ];

        $dispatcher = $this->factory->create($data);

        // Проверяем, что ошибка залогирована (путь "invalid" не проходит схему)
        $errorMessages = $this->logger->getMessages('error');
        $this->assertCount(1, $errorMessages);
        $this->assertInstanceOf(\InvalidArgumentException::class, $errorMessages[0]);
        $this->assertStringContainsString('does not match schema', $errorMessages[0]->getMessage());

        // debug только для валидного маршрута
        $debugMessages = $this->logger->getMessages('debug');
        $this->assertCount(1, $debugMessages);
        $this->assertEquals('Added route: [GET] /valid', $debugMessages[0]);

        // Валидный маршрут должен быть
        $result = $dispatcher->dispatch('GET', '/valid');
        $this->assertEquals(Dispatcher::FOUND, $result[0]);

        // Невалидный маршрут не добавлен
        $result = $dispatcher->dispatch('GET', 'invalid');
        $this->assertEquals(Dispatcher::NOT_FOUND, $result[0]);
    }

    public function testCreateWithEmptyData(): void
    {
        $data = [];

        $dispatcher = $this->factory->create($data);

        $this->assertEmpty($this->logger->getMessages('debug'));
        $this->assertEmpty($this->logger->getMessages('error'));

        $result = $dispatcher->dispatch('GET', '/any');
        $this->assertEquals(Dispatcher::NOT_FOUND, $result[0]);
    }

    public function testCreateWithOptions(): void
    {
        $options = ['cacheFile' => '/tmp/routes.cache'];
        $factory = new DispatcherFactory(
            migrate: static fn(array $data): array => $data,
            validator: new MockSchemaValidator(),
            logger: $this->logger,
            options: $options,
        );

        $data = [
            [
                'request' => [
                    'method' => 'GET',
                    'path' => '/test',
                ],
                'response' => ['body' => 'ok'],
            ],
        ];

        $dispatcher = $factory->create($data);

        $debugMessages = $this->logger->getMessages('debug');
        $this->assertCount(1, $debugMessages);
        $this->assertEquals('Added route: [GET] /test', $debugMessages[0]);

        $result = $dispatcher->dispatch('GET', '/test');
        $this->assertEquals(Dispatcher::FOUND, $result[0]);

        $expected = [
            'request' => [
                'method' => 'GET',
                'path' => '/test',
            ],
            'response' => ['body' => 'ok'],
        ];
        $this->assertEquals($expected, $result[1]);
    }

    public function testWarnsOnceWhenDataMigrated(): void
    {
        $factory = new DispatcherFactory(
            migrate: static fn(array $data): array => $data + ['version' => 2],
            validator: new MockSchemaValidator(),
            logger: $this->logger,
        );

        $factory->create([
            ['request' => ['method' => 'GET', 'path' => '/a']],
            ['request' => ['method' => 'GET', 'path' => '/b']],
        ]);

        $warnings = $this->logger->getMessages('warning');
        $this->assertCount(1, $warnings);
        $this->assertStringContainsString('bin/migrate', $warnings[0]);
    }

    public function testDoesNotWarnWhenDataUnchanged(): void
    {
        $this->factory->create([
            ['request' => ['method' => 'GET', 'path' => '/a']],
        ]);

        $this->assertCount(0, $this->logger->getMessages('warning'));
    }
}
