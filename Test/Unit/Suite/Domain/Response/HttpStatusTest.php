<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Domain\Response;

use Lav45\MockServer\Domain\Model\Response\HttpStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class HttpStatusTest extends TestCase
{
    #[DataProvider('successCreateDataProvider')]
    public function testSuccessCreate(int $code): void
    {
        $result = new HttpStatus($code)->value;
        $this->assertEquals($code, $result);
    }

    public static function successCreateDataProvider(): array
    {
        return [
            [100],
            [150],
            [200],
            [250],
            [300],
            [350],
            [400],
            [450],
            [500],
            [550],
            [599],
        ];
    }

    #[DataProvider('failedCreateDataProvider')]
    public function testFailedCreate(int $code): void
    {
        $this->expectException(\AssertionError::class);
        new HttpStatus($code);
    }

    public static function failedCreateDataProvider(): array
    {
        return [
            [1],
            [99],
            [600],
            [1000],
        ];
    }
}
