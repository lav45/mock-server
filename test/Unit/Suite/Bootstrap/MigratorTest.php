<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Bootstrap;

use Lav45\MockServer\Bootstrap\Migrator;
use PHPUnit\Framework\TestCase;

final class MigratorTest extends TestCase
{
    private \Closure $migrate;

    protected function setUp(): void
    {
        $this->migrate = Migrator::create(\dirname(__DIR__, 4) . '/migrates');
    }

    public function testMigrateDeprecatedFormat(): void
    {
        $migrate = $this->migrate;

        $data = [
            'request' => [
                'method' => 'POST',
                'url' => '/user/{id}',
            ],
            'response' => [
                'json' => [
                    'id' => '{request.urlParams.id}',
                    'page' => '{request.get.page}',
                    'content' => '{request.body}',
                ],
            ],
            'webhooks' => [
                [
                    'url' => 'http://example.com/hook',
                    'text' => 'id={request.urlParams.id}',
                ],
                [
                    'url' => 'http://example.com/hook',
                    'json' => ['id' => '{request.get.id}'],
                ],
            ],
        ];

        $expected = [
            'version' => 8,
            'request' => [
                'method' => 'POST',
                'path' => '/user/{id}',
            ],
            'response' => [
                'body' => [
                    'id' => '{request.params.id}',
                    'page' => '{request.query.page}',
                    'content' => '{request.rawBody}',
                ],
                'headers' => [
                    'content-type' => 'application/json',
                ],
            ],
            'webhooks' => [
                [
                    'url' => 'http://example.com/hook',
                    'body' => 'id={request.params.id}',
                ],
                [
                    'url' => 'http://example.com/hook',
                    'body' => ['id' => '{request.query.id}'],
                    'headers' => [
                        'content-type' => 'application/json',
                    ],
                ],
            ],
        ];

        $this->assertSame($expected, $migrate($data));
    }

    public function testMigrateDeprecatedDataType(): void
    {
        $migrate = $this->migrate;

        $data = [
            'request' => [
                'method' => 'GET',
                'path' => '/users',
            ],
            'response' => [
                'type' => 'data',
                'json' => [
                    ['id' => 1],
                    ['id' => 2],
                ],
            ],
        ];

        $expected = [
            'version' => 8,
            'request' => [
                'method' => 'GET',
                'path' => '/users',
            ],
            'response' => [
                'type' => 'data',
                'items' => [
                    ['id' => 1],
                    ['id' => 2],
                ],
            ],
        ];

        $this->assertSame($expected, $migrate($data));
    }

    public function testActualFormatNotChanged(): void
    {
        $migrate = $this->migrate;

        $data = [
            'version' => 8,
            'request' => [
                'method' => 'POST',
                'path' => '/user/{id}',
            ],
            'response' => [
                'status' => 200,
                'headers' => [
                    'content-type' => 'application/json',
                ],
                'body' => [
                    'id' => '{request.params.id}',
                ],
            ],
            'webhooks' => [
                [
                    'url' => 'http://example.com/hook',
                    'body' => 'plain text',
                ],
            ],
        ];

        $this->assertSame($data, $migrate($data));
    }

    public function testSkipMigrationsByVersion(): void
    {
        $migrate = $this->migrate;

        // Устаревший формат, но версия актуальная — миграции не применяются
        $data = [
            'version' => 8,
            'request' => [
                'method' => 'GET',
                'url' => '/user/{id}',
            ],
            'response' => [
                'json' => ['id' => '{request.urlParams.id}'],
            ],
        ];

        $this->assertSame($data, $migrate($data));
    }

    public function testUpgradeOutdatedVersion(): void
    {
        $migrate = $this->migrate;

        // Версия ниже текущей — данные обновляются, версия прописывается первой
        $data = [
            'version' => 2,
            'request' => [
                'path' => '/response/content/status',
            ],
            'response' => [
                'type' => 'content',
                'status' => 401,
                'body' => 'Unauthorized',
            ],
        ];

        $expected = [
            'version' => 8,
            'request' => [
                'path' => '/response/content/status',
            ],
            'response' => [
                'type' => 'content',
                'status' => 401,
                'body' => 'Unauthorized',
            ],
        ];

        $this->assertSame($expected, $migrate($data));
    }

    public function testCreateWithoutMigrations(): void
    {
        $migrate = Migrator::create('/tmp/' . \uniqid('empty_migrates_', true));

        $data = ['request' => ['method' => 'GET', 'path' => '/users']];

        $this->assertSame($data, $migrate($data));
    }
}
