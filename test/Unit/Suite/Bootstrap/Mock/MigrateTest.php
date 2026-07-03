<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Bootstrap\Mock;

use Lav45\MockServer\Bootstrap\Mock\Migrate;
use Lav45\MockServer\Test\Unit\Components\FakeLogger;
use PHPUnit\Framework\TestCase;

final class MigrateTest extends TestCase
{
    public function testReturnsMigratedDataWhenNextIsNull(): void
    {
        $migrate = new Migrate(static fn(array $data): array => $data + ['version' => 2]);

        $result = ($migrate(null))(['request' => ['path' => '/a']]);

        $this->assertSame(['request' => ['path' => '/a'], 'version' => 2], $result);
    }

    public function testChainsNextWithMigratedData(): void
    {
        $migrate = new Migrate(static fn(array $data): array => $data + ['version' => 2]);
        $next = static fn(array $data): array => $data + ['validated' => true];

        $result = ($migrate($next))(['request' => ['path' => '/a']]);

        $this->assertSame(['request' => ['path' => '/a'], 'version' => 2, 'validated' => true], $result);
    }

    public function testWarnsOnceWhenDataMigrated(): void
    {
        $logger = new FakeLogger();
        $migrate = new Migrate(static fn(array $data): array => $data + ['version' => 2], $logger);
        $run = $migrate(null);

        $run(['request' => ['path' => '/a']]);
        $run(['request' => ['path' => '/b']]);

        $warnings = $logger->getMessages('warning');
        $this->assertCount(1, $warnings);
        $this->assertStringContainsString('bin/migrate', $warnings[0]);
    }

    public function testDoesNotWarnWhenDataUnchanged(): void
    {
        $logger = new FakeLogger();
        $migrate = new Migrate(static fn(array $data): array => $data, $logger);

        ($migrate(null))(['request' => ['path' => '/a']]);

        $this->assertCount(0, $logger->getMessages('warning'));
    }
}
