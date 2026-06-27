<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Domain\Response;

use Lav45\MockServer\Domain\ValueObject\HttpStatus;
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
        $this->expectException(\InvalidArgumentException::class);
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

    public function testInformationalBoundary(): void
    {
        $status = new HttpStatus(100);
        $this->assertTrue($status->isInformational(199));
        $this->assertFalse($status->isInformational(200));
    }

    public function testSuccessfulBoundary(): void
    {
        $status = new HttpStatus(200);
        $this->assertTrue($status->isSuccessful(299));
        $this->assertFalse($status->isSuccessful(300));
    }

    public function testRedirectBoundary(): void
    {
        $status = new HttpStatus(300);
        $this->assertTrue($status->isRedirect(399));
        $this->assertFalse($status->isRedirect(400));
    }

    public function testClientErrorBoundary(): void
    {
        $status = new HttpStatus(400);
        $this->assertTrue($status->isClientError(499));
        $this->assertFalse($status->isClientError(500));
    }

    public function testServerErrorBoundary(): void
    {
        $status = new HttpStatus(500);
        $this->assertTrue($status->isServerError(599));
        $this->assertFalse($status->isServerError(600));
    }
}
