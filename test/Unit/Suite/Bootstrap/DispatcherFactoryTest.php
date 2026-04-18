<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Bootstrap;

use FastRoute\Dispatcher;
use Lav45\MockServer\Bootstrap\DispatcherFactory;
use Lav45\MockServer\Test\Unit\Components\FakeLogger;
use PHPUnit\Framework\TestCase;

final class DispatcherFactoryTest extends TestCase
{
    private FakeLogger $logger;
    private DispatcherFactory $factory;

    protected function setUp(): void
    {
        $this->logger = new FakeLogger();
        $this->factory = new DispatcherFactory($this->logger);
    }

    protected function tearDown(): void
    {
        $this->logger->reset();
    }

    public function testCreateWithValidRoutes(): void
    {
        $data = [
            [
                [
                    'request' => [
                        'method' => 'GET',
                        'url' => '/users',
                    ],
                    'response' => ['id' => 1],
                ],
                [
                    'request' => [
                        'method' => ['POST', 'PUT'],
                        'url' => '/users',
                    ],
                    'response' => ['created' => true],
                ],
            ],
            [
                [
                    'request' => [
                        'method' => 'DELETE',
                        'url' => '/users/1',
                    ],
                    'response' => ['deleted' => true],
                ],
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

        // Проверяем маршруты
        $this->assertInstanceOf(Dispatcher::class, $dispatcher);

        $expends = [
            'request' => [
                'method' => 'GET',
                'url' => '/users',
            ],
            'response' => ['id' => 1],
        ];
        $result = $dispatcher->dispatch('GET', '/users');
        $this->assertEquals(Dispatcher::FOUND, $result[0]);
        $this->assertEquals($expends, $result[1]);

        $expends = [
            'request' => [
                'method' => ['POST', 'PUT'],
                'url' => '/users',
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
                'url' => '/users/1',
            ],
            'response' => ['deleted' => true],
        ];
        $result = $dispatcher->dispatch('DELETE', '/users/1');
        $this->assertEquals(Dispatcher::FOUND, $result[0]);
        $this->assertEquals($expends, $result[1]);

        $result = $dispatcher->dispatch('GET', '/unknown');
        $this->assertEquals(Dispatcher::NOT_FOUND, $result[0]);
    }

    public function testCreateWithInvalidUrl(): void
    {
        $data = [
            [
                [
                    'request' => [
                        'method' => 'GET',
                        'url' => '/valid',
                    ],
                    'response' => 'ok',
                ],
                [
                    'request' => [
                        'method' => 'GET',
                        'url' => 'invalid',
                    ],
                    'response' => 'should not be added',
                ],
            ],
        ];

        $dispatcher = $this->factory->create($data);

        // Проверяем, что ошибка залогирована
        $errorMessages = $this->logger->getMessages('error');
        $this->assertCount(1, $errorMessages);
        $this->assertInstanceOf(\InvalidArgumentException::class, $errorMessages[0]);
        $this->assertStringContainsString('Invalid url: "invalid"', $errorMessages[0]->getMessage());

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
        $factory = new DispatcherFactory($this->logger, $options);

        $data = [
            [
                [
                    'request' => [
                        'method' => 'GET',
                        'url' => '/test',
                    ],
                    'response' => 'ok',
                ],
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
                'url' => '/test',
            ],
            'response' => 'ok',
        ];
        $this->assertEquals($expected, $result[1]);
    }
}
