<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Bootstrap;

use Lav45\MockServer\Bootstrap\FileSystem;
use PHPUnit\Framework\TestCase;

use function Amp\File\createDirectory;
use function Amp\File\deleteDirectory;
use function Amp\File\deleteFile;
use function Amp\File\touch;

final class FileSystemTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = \sys_get_temp_dir() . '/fs_test_' . \uniqid('', true);
        createDirectory($this->tempDir);
    }

    protected function tearDown(): void
    {
        foreach (\glob($this->tempDir . '/*/*') as $file) {
            deleteFile($file);
        }
        foreach (\glob($this->tempDir . '/*') as $path) {
            \is_dir($path) ? deleteDirectory($path) : deleteFile($path);
        }
        deleteDirectory($this->tempDir);
    }

    public function testGetFileListWithNoFilter(): void
    {
        touch($this->tempDir . '/a.json');
        touch($this->tempDir . '/b.json');

        $result = FileSystem::getFileList($this->tempDir);

        $this->assertSame([
            $this->tempDir . '/a.json' => $this->tempDir . '/a.json',
            $this->tempDir . '/b.json' => $this->tempDir . '/b.json',
        ], $result);
    }

    public function testGetFileListWithAcceptingFilter(): void
    {
        touch($this->tempDir . '/file.json');
        touch($this->tempDir . '/file.txt');

        $result = FileSystem::getFileList($this->tempDir, static fn(string $path) => \str_ends_with($path, '.json'));

        $this->assertSame([
            $this->tempDir . '/file.json' => $this->tempDir . '/file.json',
        ], $result);
    }

    public function testGetFileListWithRejectingFilter(): void
    {
        touch($this->tempDir . '/file.txt');

        $result = FileSystem::getFileList($this->tempDir, static fn(string $path) => \str_ends_with($path, '.json'));

        $this->assertSame([], $result);
    }

    public function testGetFileListRecursive(): void
    {
        $subDir = $this->tempDir . '/subdir';
        createDirectory($subDir);
        touch($this->tempDir . '/root.json');
        touch($subDir . '/nested.json');

        $result = FileSystem::getFileList($this->tempDir);

        $this->assertSame([
            $this->tempDir . '/root.json' => $this->tempDir . '/root.json',
            $subDir . '/nested.json' => $subDir . '/nested.json',
        ], $result);
    }

    public function testGetFileListEmptyDirectory(): void
    {
        $result = FileSystem::getFileList($this->tempDir);

        $this->assertSame([], $result);
    }
}
