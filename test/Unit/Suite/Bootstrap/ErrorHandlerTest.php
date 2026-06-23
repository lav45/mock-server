<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Bootstrap;

use Lav45\MockServer\Bootstrap\ErrorHandler;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ErrorHandlerTest extends TestCase
{
    private ErrorHandler $handler;

    protected function setUp(): void
    {
        $this->handler = new ErrorHandler();
    }

    public function testResponseContentType(): void
    {
        $response = $this->handler->handleError(404);
        $this->assertSame('application/json', $response->getHeader('content-type'));
    }

    #[DataProvider('statusProvider')]
    public function testResponseStatus(int $status): void
    {
        $response = $this->handler->handleError($status);
        $this->assertSame($status, $response->getStatus());
    }

    public static function statusProvider(): array
    {
        return [
            [404],
            [405],
            [500],
            [503],
        ];
    }

    #[DataProvider('bodyProvider')]
    public function testResponseBody(int $status, string|null $reason, array $expected): void
    {
        $response = $this->handler->handleError($status, $reason);
        $body = \json_decode($response->getBody(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame($expected, $body);
    }

    public static function bodyProvider(): array
    {
        return [
            [404, null, ['status' => 404, 'message' => 'Not Found']],
            [405, null, ['status' => 405, 'message' => 'Method Not Allowed']],
            [500, null, ['status' => 500, 'message' => 'Internal Server Error']],
            [500, 'Custom Error', ['status' => 500, 'message' => 'Custom Error']],
        ];
    }
}
