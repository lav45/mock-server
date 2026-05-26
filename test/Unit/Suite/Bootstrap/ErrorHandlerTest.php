<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Bootstrap;

use Amp\Http\HttpStatus;
use Lav45\MockServer\Bootstrap\ErrorHandler;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function Amp\ByteStream\buffer;

final class ErrorHandlerTest extends TestCase
{
    private ErrorHandler $handler;

    protected function setUp(): void
    {
        $this->handler = new ErrorHandler();
    }

    public function testResponseContentType(): void
    {
        $response = $this->handler->handleError(HttpStatus::NOT_FOUND);
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
            [HttpStatus::NOT_FOUND],
            [HttpStatus::METHOD_NOT_ALLOWED],
            [HttpStatus::INTERNAL_SERVER_ERROR],
            [HttpStatus::SERVICE_UNAVAILABLE],
        ];
    }

    #[DataProvider('bodyProvider')]
    public function testResponseBody(int $status, string|null $reason, array $expected): void
    {
        $response = $this->handler->handleError($status, $reason);
        $body = \json_decode(buffer($response->getBody()), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame($expected, $body);
    }

    public static function bodyProvider(): array
    {
        return [
            [HttpStatus::NOT_FOUND, null, ['status' => 404, 'message' => 'Not Found']],
            [HttpStatus::METHOD_NOT_ALLOWED, null, ['status' => 405, 'message' => 'Method Not Allowed']],
            [HttpStatus::INTERNAL_SERVER_ERROR, null, ['status' => 500, 'message' => 'Internal Server Error']],
            [HttpStatus::INTERNAL_SERVER_ERROR, 'Custom Error', ['status' => 500, 'message' => 'Custom Error']],
        ];
    }
}
