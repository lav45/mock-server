<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite;

use Lav45\MockServer\Config;
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
        $this->assertSame(Level::Info, $this->config->getLogLevel());
        $this->assertSame(0.2, $this->config->getFileWatchTimeout());
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
        // Create a temporary readable directory
        $tempDir = \sys_get_temp_dir() . '/mocks_test_' . \uniqid('', true);
        createDirectory($tempDir);
        if (isDirectory($tempDir) === false) {
            $this->markTestSkipped("Cannot create temporary directory {$tempDir}");
        }

        try {
            $this->config->mocks($tempDir);
            $this->assertSame($tempDir, $this->config->getMocksPath());
        } finally {
            // Clean up
            if (isDirectory($tempDir)) {
                deleteDirectory($tempDir);
            }
        }
    }

    #[DataProvider('invalidMocksPathProvider')]
    public function testMocksWithInvalidPathThrowsException(string $invalidPath, bool $createFile = false): void
    {
        if ($createFile) {
            // Create a file instead of a directory to test is_dir failure
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
    public function testLogWithValidLevels(string $levelName, Level $expectedLevel): void
    {
        $this->config->log($levelName);
        $this->assertSame($expectedLevel, $this->config->getLogLevel());
    }

    public static function validLogLevelProvider(): array
    {
        return [
            ['debug', Level::Debug],
            ['info', Level::Info],
            ['notice', Level::Notice],
            ['warning', Level::Warning],
            ['error', Level::Error],
            ['critical', Level::Critical],
            ['alert', Level::Alert],
            ['emergency', Level::Emergency],
            ['DEBUG', Level::Debug],
            ['InFo', Level::Info],
        ];
    }

    public function testLogWithInvalidLevelThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid log level');
        $this->config->log('invalid_level');
    }

    #[DataProvider('validFileWatchTimeoutProvider')]
    public function testFileWatchWithValidTimeouts(string|float $timeout, float $expected): void
    {
        $this->config->fileWatch($timeout);
        $this->assertSame($expected, $this->config->getFileWatchTimeout());
    }

    public static function validFileWatchTimeoutProvider(): array
    {
        return [
            [0.5, 0.5],
            [1.0, 1.0],
            ["2", 2.0],
            ["0.123", 0.123],
            [1, 1.0],
            [0, 0.0],
            [0.0, 0.0],
            ["0", 0.0],
            ["0.0", 0.0],
        ];
    }

    #[DataProvider('invalidFileWatchTimeoutProvider')]
    public function testFileWatchWithInvalidTimeoutThrowsException(mixed $invalidTimeout): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid timeout');
        $this->config->fileWatch($invalidTimeout);
    }

    public static function invalidFileWatchTimeoutProvider(): array
    {
        return [
            ['not_a_float'],
            [-0.1],
            ["-1.5"],
        ];
    }
}
