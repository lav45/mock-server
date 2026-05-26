<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Bootstrap;

use Lav45\MockServer\Bootstrap\Config;
use Monolog\Level;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function Amp\File\createDirectory;
use function Amp\File\deleteDirectory;
use function Amp\File\deleteFile;
use function Amp\File\isDirectory;
use function Amp\File\isFile;
use function Amp\File\touch;

final class ConfigTest extends TestCase
{
    private Config $config;

    protected function setUp(): void
    {
        $this->config = new Config();
    }

    public function testDefaultValues(): void
    {
        $this->assertSame(8080, $this->config->getPort());
        $this->assertSame('/app/mocks', $this->config->getMocksPath());
        $this->assertSame('en_US', $this->config->getLocale());
        $this->assertSame(Level::Info->value, $this->config->getLogLevel());
        $this->assertSame(
            ['host', 'content-length', 'connection', 'keep-alive', 'transfer-encoding'],
            $this->config->getFilterHeaders(),
        );
    }

    public function testListenWithValidValues(): void
    {
        $this->config->port(9090);
        $this->assertSame(9090, $this->config->getPort());

        $this->config->port('8888');
        $this->assertSame(8888, $this->config->getPort());
    }

    #[DataProvider('invalidPortProvider')]
    public function testListenWithInvalidPortThrowsException(mixed $invalidPort): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid mock port');
        $this->config->port(port: $invalidPort);
    }

    public static function invalidPortProvider(): array
    {
        return [
            [-1],
            ['-5'],
            [65536],
            ['not_a_port'],
        ];
    }

    public function testMocksWithValidPath(): void
    {
        $tempDir = \sys_get_temp_dir() . '/mocks_test_' . \uniqid('', true);
        createDirectory($tempDir);
        if (isDirectory($tempDir) === false) {
            $this->markTestSkipped("Cannot create temporary directory {$tempDir}");
        }

        try {
            $this->config->mocks($tempDir);
            $this->assertSame($tempDir, $this->config->getMocksPath());
        } finally {
            if (isDirectory($tempDir)) {
                deleteDirectory($tempDir);
            }
        }
    }

    #[DataProvider('invalidMocksPathProvider')]
    public function testMocksWithInvalidPathThrowsException(string $invalidPath, bool $createFile = false): void
    {
        if ($createFile) {
            $tempFile = \sys_get_temp_dir() . '/' . \basename($invalidPath);
            touch($tempFile);
            $invalidPath = $tempFile;
        }

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid mocks path');

        try {
            $this->config->mocks($invalidPath);
        } finally {
            if ($createFile && isFile($invalidPath)) {
                deleteFile($invalidPath);
            }
        }
    }

    public static function invalidMocksPathProvider(): array
    {
        return [
            ['/non_existent_path_' . \uniqid('', true)],
            ['a_file_instead_of_dir', true],
        ];
    }

    public function testLocaleWithValidValues(): void
    {
        $this->config->locale('fr_FR');
        $this->assertSame('fr_FR', $this->config->getLocale());

        $this->config->locale('en-US'); // Should be canonicalized
        $this->assertSame('en_US', $this->config->getLocale());
    }

    public function testLocaleWithInvalidValueThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid locale');
        $this->config->locale('invalid_locale_format');
    }

    #[DataProvider('validLogLevelProvider')]
    public function testLogWithValidLevels(string $levelName, int $expectedLevel): void
    {
        $this->config->log($levelName);
        $this->assertSame($expectedLevel, $this->config->getLogLevel());
    }

    public static function validLogLevelProvider(): array
    {
        return [
            ['debug', Level::Debug->value],
            ['info', Level::Info->value],
            ['notice', Level::Notice->value],
            ['warning', Level::Warning->value],
            ['error', Level::Error->value],
            ['critical', Level::Critical->value],
            ['alert', Level::Alert->value],
            ['emergency', Level::Emergency->value],
            ['DEBUG', Level::Debug->value],
            ['InFo', Level::Info->value],
        ];
    }

    public function testLogWithInvalidLevelThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid log level');
        $this->config->log('invalid_level');
    }

    public function testFilterHeadersWithFalseKeepsDefault(): void
    {
        $this->config->filterHeaders(false);
        $this->assertSame(
            ['host', 'content-length', 'connection', 'keep-alive', 'transfer-encoding'],
            $this->config->getFilterHeaders(),
        );
    }

    #[DataProvider('filterHeadersProvider')]
    public function testFilterHeadersParsesInput(string $input, array $expected): void
    {
        $this->config->filterHeaders($input);
        $this->assertArraysHaveEqualValues($expected, $this->config->getFilterHeaders());
    }

    public static function filterHeadersProvider(): array
    {
        return [
            'simple list' => ['host,content-length', ['host', 'content-length']],
            'uppercase' => ['HOST,Content-Length', ['host', 'content-length']],
            'spaces around' => ['host, content-length , connection', ['host', 'content-length', 'connection']],
            'empty parts' => ['host,,content-length', ['host', 'content-length']],
        ];
    }
}
