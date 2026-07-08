<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Driver;

use Lav45\MockServer\Driver\Config;
use Lav45\MockServer\Extension\Content\ContentExtension;
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
        $this->assertSame(33_554_432, $this->config->getMaxBufferSize());
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
        $this->expectExceptionMessageIsOrContains('Invalid mock port');
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
        $this->expectExceptionMessageIsOrContains('Invalid mocks path');

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
        $this->expectExceptionMessageIsOrContains('Invalid locale');
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
        $this->expectExceptionMessageIsOrContains('Invalid log level');
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

    #[DataProvider('emptyPathProvider')]
    public function testFromFileWithoutPathReturnsDefaults(string|false $path): void
    {
        $config = Config::fromFile($path);

        $this->assertSame(8080, $config->getPort());
        $this->assertSame('/app/mocks', $config->getMocksPath());
        $this->assertSame([], $config->getExtensions());
    }

    public static function emptyPathProvider(): array
    {
        return [
            'false' => [false],
            'empty string' => [''],
        ];
    }

    public function testFromFileParsesCustomFile(): void
    {
        $path = \sys_get_temp_dir() . '/config_' . \uniqid('', true) . '.yaml';
        \file_put_contents($path, "port: 9090\nlocale: fr_FR\nextensions:\n  - class: " . ContentExtension::class . "\n");

        try {
            $config = Config::fromFile($path);
            $this->assertSame(9090, $config->getPort());
            $this->assertSame('fr_FR', $config->getLocale());
            $this->assertSame(ContentExtension::class, $config->getExtensions()[0]->class);
        } finally {
            \unlink($path);
        }
    }

    public function testFromFileParsesCamelCaseKeys(): void
    {
        $path = \sys_get_temp_dir() . '/config_' . \uniqid('', true) . '.yaml';
        \file_put_contents($path, "logLevel: debug\nfilterHeaders: [host, x-test]\n");

        try {
            $config = Config::fromFile($path);
            $this->assertSame(Level::Debug->value, $config->getLogLevel());
            $this->assertSame(['host', 'x-test'], $config->getFilterHeaders());
        } finally {
            \unlink($path);
        }
    }

    public function testFromFileParsesSchema(): void
    {
        $schemaFile = \sys_get_temp_dir() . '/schema_' . \uniqid('', true) . '.json';
        touch($schemaFile);
        $path = \sys_get_temp_dir() . '/config_' . \uniqid('', true) . '.yaml';
        \file_put_contents($path, "schema: {$schemaFile}\n");

        try {
            $this->assertSame($schemaFile, Config::fromFile($path)->getSchema());
        } finally {
            \unlink($path);
            deleteFile($schemaFile);
        }
    }

    public function testFromFileThrowsForMissingPath(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('Invalid config path');
        Config::fromFile('/non_existent_config_' . \uniqid('', true) . '.yaml');
    }

    public function testFromFileThrowsForNonArrayContent(): void
    {
        $path = \sys_get_temp_dir() . '/config_' . \uniqid('', true) . '.yaml';
        \file_put_contents($path, 'just a scalar');

        try {
            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessageIsOrContains('Invalid config file');
            Config::fromFile($path);
        } finally {
            \unlink($path);
        }
    }

    public function testExtensionsThrowsWhenClassMissing(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('Invalid extension: missing class');
        $this->config->extensions([['config' => ['allow_origin' => '*']]]);
    }

    public function testSchemaDefaultsToNull(): void
    {
        $this->assertNull($this->config->getSchema());
    }

    public function testSchemaWithFalseKeepsNull(): void
    {
        $this->config->schema(false);
        $this->assertNull($this->config->getSchema());
    }

    public function testSchemaWithValidPath(): void
    {
        $schemaFile = \sys_get_temp_dir() . '/schema_' . \uniqid('', true) . '.json';
        touch($schemaFile);

        try {
            $this->config->schema($schemaFile);
            $this->assertSame($schemaFile, $this->config->getSchema());
        } finally {
            deleteFile($schemaFile);
        }
    }

    #[DataProvider('invalidSchemaPathProvider')]
    public function testSchemaWithInvalidPathThrows(string $invalidPath): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('Invalid schema path');
        $this->config->schema($invalidPath);
    }

    public static function invalidSchemaPathProvider(): array
    {
        return [
            'missing file' => ['/non_existent_schema_' . \uniqid('', true) . '.json'],
            'directory instead of file' => [\sys_get_temp_dir()],
        ];
    }

    #[DataProvider('maxBufferSizeProvider')]
    public function testMaxBufferSizeConvertsMegabytesToBytes(string|int $megabytes, int $expectedBytes): void
    {
        $this->config->maxBufferSize($megabytes);
        $this->assertSame($expectedBytes, $this->config->getMaxBufferSize());
    }

    public static function maxBufferSizeProvider(): array
    {
        return [
            'int megabytes' => [64, 64 * 1024 * 1024],
            'string megabytes' => ['16', 16 * 1024 * 1024],
        ];
    }

    #[DataProvider('nonNumericMaxBufferSizeProvider')]
    public function testMaxBufferSizeWithNonNumericKeepsDefault(string|false $value): void
    {
        $this->config->maxBufferSize($value);
        $this->assertSame(33_554_432, $this->config->getMaxBufferSize());
    }

    public static function nonNumericMaxBufferSizeProvider(): array
    {
        return [
            'false' => [false],
            'empty string' => [''],
            'not a number' => ['not_a_number'],
        ];
    }

    public function testFromFileParsesMaxBufferSize(): void
    {
        $path = \sys_get_temp_dir() . '/config_' . \uniqid('', true) . '.yaml';
        \file_put_contents($path, "maxBufferSize: 8\n");

        try {
            $this->assertSame(8 * 1024 * 1024, Config::fromFile($path)->getMaxBufferSize());
        } finally {
            \unlink($path);
        }
    }

    public function testTlsDefaultsToNull(): void
    {
        $this->assertNull($this->config->getTls());
    }

    public function testTlsWithFalseKeepsNull(): void
    {
        $this->config->tls(false);
        $this->assertNull($this->config->getTls());
    }

    public function testTlsWithValidConfig(): void
    {
        $cert = \sys_get_temp_dir() . '/cert_' . \uniqid('', true) . '.pem';
        $key = \sys_get_temp_dir() . '/key_' . \uniqid('', true) . '.pem';
        touch($cert);
        touch($key);

        try {
            $this->config->tls(['port' => 9443, 'cert' => $cert, 'key' => $key, 'passphrase' => 'secret']);
            $tls = $this->config->getTls();
            $this->assertNotNull($tls);
            $this->assertSame(9443, $tls->port);
            $this->assertSame($cert, $tls->cert);
            $this->assertSame($key, $tls->key);
            $this->assertSame('secret', $tls->passphrase);
        } finally {
            deleteFile($cert);
            deleteFile($key);
        }
    }

    public function testTlsDefaultsPortAndKeyToCert(): void
    {
        $cert = \sys_get_temp_dir() . '/cert_' . \uniqid('', true) . '.pem';
        touch($cert);

        try {
            $this->config->tls(['cert' => $cert]);
            $tls = $this->config->getTls();
            $this->assertNotNull($tls);
            $this->assertSame(8443, $tls->port);
            $this->assertSame($cert, $tls->cert);
            $this->assertSame($cert, $tls->key);
            $this->assertNull($tls->passphrase);
        } finally {
            deleteFile($cert);
        }
    }

    public function testTlsWithInvalidPortThrows(): void
    {
        $cert = \sys_get_temp_dir() . '/cert_' . \uniqid('', true) . '.pem';
        touch($cert);

        try {
            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessageIsOrContains('Invalid tls port');
            $this->config->tls(['port' => 70000, 'cert' => $cert]);
        } finally {
            deleteFile($cert);
        }
    }

    #[DataProvider('invalidTlsCertProvider')]
    public function testTlsWithInvalidCertThrows(array $tls): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('Invalid tls cert');
        $this->config->tls($tls);
    }

    public static function invalidTlsCertProvider(): array
    {
        return [
            'missing cert' => [['port' => 8443]],
            'cert not a file' => [['cert' => '/non_existent_cert_' . \uniqid('', true) . '.pem']],
            'cert is a directory' => [['cert' => \sys_get_temp_dir()]],
        ];
    }

    public function testTlsWithInvalidKeyThrows(): void
    {
        $cert = \sys_get_temp_dir() . '/cert_' . \uniqid('', true) . '.pem';
        touch($cert);

        try {
            $this->expectException(\InvalidArgumentException::class);
            $this->expectExceptionMessageIsOrContains('Invalid tls key');
            $this->config->tls(['cert' => $cert, 'key' => '/non_existent_key_' . \uniqid('', true) . '.pem']);
        } finally {
            deleteFile($cert);
        }
    }

    public function testFromFileParsesTls(): void
    {
        $cert = \sys_get_temp_dir() . '/cert_' . \uniqid('', true) . '.pem';
        $key = \sys_get_temp_dir() . '/key_' . \uniqid('', true) . '.pem';
        touch($cert);
        touch($key);
        $path = \sys_get_temp_dir() . '/config_' . \uniqid('', true) . '.yaml';
        \file_put_contents($path, "tls:\n  port: 8443\n  cert: {$cert}\n  key: {$key}\n");

        try {
            $tls = Config::fromFile($path)->getTls();
            $this->assertNotNull($tls);
            $this->assertSame(8443, $tls->port);
            $this->assertSame($cert, $tls->cert);
            $this->assertSame($key, $tls->key);
        } finally {
            \unlink($path);
            deleteFile($cert);
            deleteFile($key);
        }
    }
}
