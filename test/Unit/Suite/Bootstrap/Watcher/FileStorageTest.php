<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Bootstrap\Watcher;

use Lav45\MockServer\Bootstrap\Watcher\MockStorage;
use Lav45\MockServer\Test\Unit\Components\FakeLogger;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

use function Amp\File\createDirectory;
use function Amp\File\deleteDirectory;
use function Amp\File\deleteFile;
use function Amp\File\write;

final class FileStorageTest extends TestCase
{
    #[DataProvider('fileFilterDataProvider')]
    public function testGetFileFilter(string $filePath, bool $expectedResult, string $message): void
    {
        $watchDir = '/tmp';
        $fileStorage = new MockStorage($watchDir, new NullLogger());
        $actualResult = $fileStorage->isFilteredFile($watchDir . DIRECTORY_SEPARATOR . $filePath);
        $this->assertSame($expectedResult, $actualResult, $message);
    }

    public static function fileFilterDataProvider(): array
    {
        return [
            'valid json' => ['file.json', true, 'Regular json file should pass'],
            'valid json in subdir' => ['subdir/file.json', true, 'Json in subdirectory should pass'],
            'hidden json' => ['.file.json', false, 'Hidden json file should not pass'],
            'not json' => ['file.txt', false, 'Non-json file should not pass'],
            'json in ignored dir starting with __' => ['__data/file.json', false, 'Json in __data directory should not pass'],
            'json in nested ignored dir' => ['subdir/__cache/file.json', false, 'Json in nested ignored directory should not pass'],
            'json with __ in name' => ['__file.json', true, 'Json with __ in filename (not directory) should pass'],
            'no extension' => ['file', false, 'File with no extension should not pass'],
        ];
    }

    public function testGetFilesLogsDebugOnSuccess(): void
    {
        $tempDir = \sys_get_temp_dir() . '/mock_storage_test_' . \uniqid('', true);
        createDirectory($tempDir);
        $filePath = $tempDir . '/test.json';

        try {
            write($filePath, '[{"request":{"path":"/"}}]');

            $logger = new FakeLogger();
            $storage = new MockStorage($tempDir, $logger);

            $this->assertSame(
                [['request' => ['path' => '/']]],
                $storage->getFiles()[$filePath],
            );
            $this->assertSame(["Parse: {$filePath}"], $logger->getMessages('debug'));
        } finally {
            deleteFile($filePath);
            deleteDirectory($tempDir);
        }
    }

    public function testGetFilesLogsErrorOnInvalidJson(): void
    {
        $tempDir = \sys_get_temp_dir() . '/mock_storage_test_' . \uniqid('', true);
        createDirectory($tempDir);
        $filePath = $tempDir . '/broken.json';

        try {
            write($filePath, 'not valid json');

            $logger = new FakeLogger();
            $storage = new MockStorage($tempDir, $logger);

            $this->assertArrayNotHasKey($filePath, $storage->getFiles());
            $this->assertCount(1, $logger->getMessages('error'));
        } finally {
            deleteFile($filePath);
            deleteDirectory($tempDir);
        }
    }
}
