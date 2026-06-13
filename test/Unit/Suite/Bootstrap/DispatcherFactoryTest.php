<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Bootstrap;

use FastRoute\Dispatcher;
use Lav45\MockServer\Bootstrap\DispatcherFactory;
use Lav45\MockServer\Bootstrap\Migrator;
use Lav45\MockServer\Test\Unit\Components\FakeLogger;
use PHPUnit\Framework\TestCase;

final class DispatcherFactoryTest extends TestCase
{
    private FakeLogger $logger;
    private DispatcherFactory $factory;

    protected function setUp(): void
    {
        $this->logger = new FakeLogger();
        $this->factory = new DispatcherFactory(static fn(array $data): array => $data, $this->logger);
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
                'response' => ['id' => 1],
            ],
            [
                'request' => [
                    'method' => ['POST', 'PUT'],
                    'path' => '/users',
                ],
                'response' => ['created' => true],
            ],
            [
                'request' => [
                    'method' => 'DELETE',
                    'path' => '/users/1',
                ],
                'response' => ['deleted' => true],
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
            'response' => ['id' => 1],
        ];
        $result = $dispatcher->dispatch('GET', '/users');
        $this->assertEquals(Dispatcher::FOUND, $result[0]);
        $this->assertEquals($expends, $result[1]);

        $expends = [
            'request' => [
                'method' => ['POST', 'PUT'],
                'path' => '/users',
            ],
            'response' => ['created' => true],
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
            'response' => ['deleted' => true],
        ];
        $result = $dispatcher->dispatch('DELETE', '/users/1');
        $this->assertEquals(Dispatcher::FOUND, $result[0]);
        $this->assertEquals($expends, $result[1]);

        $result = $dispatcher->dispatch('GET', '/unknown');
        $this->assertEquals(Dispatcher::NOT_FOUND, $result[0]);
    }

    public function testCreateWithDeprecatedMockFormat(): void
    {
        $data = [
            [
                'request' => [
                    'method' => 'GET',
                    'url' => '/old/1',
                ],
                'response' => [
                    'body' => 'first',
                ],
            ],
            [
                'request' => [
                    'method' => 'GET',
                    'url' => '/old/2',
                ],
                'response' => [
                    'body' => 'second',
                ],
            ],
        ];

        $factory = new DispatcherFactory(Migrator::create(\dirname(__DIR__, 2) . '/migrates'), $this->logger);
        $dispatcher = $factory->create($data);

        // Один warning независимо от количества устаревших mock
        $warningMessages = $this->logger->getMessages('warning');
        $this->assertCount(1, $warningMessages);
        $this->assertStringContainsString('bin/migrate', $warningMessages[0]);

        $this->assertEmpty($this->logger->getMessages('error'));

        // Маршруты зарегистрированы с мигрированными данными
        $expected = [
            'version' => 2,
            'request' => [
                'method' => 'GET',
                'path' => '/old/1',
            ],
            'response' => [
                'body' => 'first',
            ],
        ];
        $result = $dispatcher->dispatch('GET', '/old/1');
        $this->assertEquals(Dispatcher::FOUND, $result[0]);
        $this->assertEquals($expected, $result[1]);

        $expected = [
            'version' => 2,
            'request' => [
                'method' => 'GET',
                'path' => '/old/2',
            ],
            'response' => [
                'body' => 'second',
            ],
        ];
        $result = $dispatcher->dispatch('GET', '/old/2');
        $this->assertEquals(Dispatcher::FOUND, $result[0]);
        $this->assertEquals($expected, $result[1]);
    }

    public function testCreateWithActualMockFormatWithoutWarnings(): void
    {
        $data = [
            [
                'version' => 2,
                'request' => [
                    'method' => 'GET',
                    'path' => '/users',
                ],
                'response' => [
                    'body' => 'ok',
                ],
            ],
        ];

        $factory = new DispatcherFactory(Migrator::create(\dirname(__DIR__, 2) . '/migrates'), $this->logger);
        $factory->create($data);

        $this->assertEmpty($this->logger->getMessages('warning'));
        $this->assertEmpty($this->logger->getMessages('error'));
    }

    public function testCreateWithCustomMigrate(): void
    {
        $factory = new DispatcherFactory(static function (array $data): array {
            $data['migrated'] = true;
            return $data;
        }, $this->logger);

        $data = [
            [
                'request' => [
                    'method' => 'GET',
                    'path' => '/users',
                ],
                'response' => [
                    'body' => 'ok',
                ],
            ],
        ];

        $dispatcher = $factory->create($data);

        $this->assertCount(1, $this->logger->getMessages('warning'));

        $result = $dispatcher->dispatch('GET', '/users');
        $this->assertEquals(Dispatcher::FOUND, $result[0]);
        $this->assertTrue($result[1]['migrated']);
    }

    public function testCreateWithInvalidUrl(): void
    {
        $data = [
            [
                'request' => [
                    'method' => 'GET',
                    'path' => '/valid',
                ],
                'response' => 'ok',
            ],
            [
                'request' => [
                    'method' => 'GET',
                    'path' => 'invalid',
                ],
                'response' => 'should not be added',
            ],
        ];

        $dispatcher = $this->factory->create($data);

        // Проверяем, что ошибка залогирована
        $errorMessages = $this->logger->getMessages('error');
        $this->assertCount(1, $errorMessages);
        $this->assertInstanceOf(\InvalidArgumentException::class, $errorMessages[0]);
        $this->assertStringContainsString('Invalid path: "invalid"', $errorMessages[0]->getMessage());

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
        $factory = new DispatcherFactory(static fn(array $data): array => $data, $this->logger, $options);

        $data = [
            [
                'request' => [
                    'method' => 'GET',
                    'path' => '/test',
                ],
                'response' => 'ok',
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
            'response' => 'ok',
        ];
        $this->assertEquals($expected, $result[1]);
    }
}
