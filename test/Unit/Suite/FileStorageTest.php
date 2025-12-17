<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite;

use Lav45\MockServer\FileStorage;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class FileStorageTest extends TestCase
{
    #[DataProvider('fileFilterDataProvider')]
    public function testGetFileFilter(string $filePath, bool $expectedResult, string $message): void
    {
        $watchDir = '/tmp';
        $fileStorage = new FileStorage($watchDir, new NullLogger());
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
}
