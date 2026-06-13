<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Bootstrap;

use Lav45\MockServer\Bootstrap\Migrator;
use PHPUnit\Framework\TestCase;

final class MigratorTest extends TestCase
{
    private \Closure $migrate;

    protected function setUp(): void
    {
        $this->migrate = Migrator::create(\dirname(__DIR__, 2) . '/migrates');
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
                'text' => 'Hello',
            ],
        ];

        $expected = [
            'version' => 2,
            'request' => [
                'method' => 'POST',
                'path' => '/user/{id}',
            ],
            'response' => [
                'body' => 'Hello',
            ],
        ];

        $this->assertSame($expected, $migrate($data));
    }

    public function testActualFormatNotChanged(): void
    {
        $migrate = $this->migrate;

        $data = [
            'version' => 2,
            'request' => [
                'method' => 'POST',
                'path' => '/user/{id}',
            ],
            'response' => [
                'status' => 200,
                'body' => [
                    'id' => '{request.params.id}',
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
            'version' => 2,
            'request' => [
                'method' => 'GET',
                'url' => '/user/{id}',
            ],
            'response' => [
                'text' => 'Hello',
            ],
        ];

        $this->assertSame($data, $migrate($data));
    }

    public function testUpgradeOutdatedVersion(): void
    {
        $migrate = $this->migrate;

        // Версия 1 — v1 пропускается (url остаётся), v2 применяется, версия прописывается первой
        $data = [
            'version' => 1,
            'request' => [
                'url' => '/user/{id}',
            ],
            'response' => [
                'text' => 'Hello',
            ],
        ];

        $expected = [
            'version' => 2,
            'request' => [
                'url' => '/user/{id}',
            ],
            'response' => [
                'body' => 'Hello',
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
