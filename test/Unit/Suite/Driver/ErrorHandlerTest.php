<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Driver;

use Lav45\MockServer\Driver\ErrorHandler;
use PHPUnit\Framework\TestCase;

use function Amp\ByteStream\buffer;

final class ErrorHandlerTest extends TestCase
{
    public function testHandleErrorSetsStatusAndReason(): void
    {
        $response = new ErrorHandler()->handleError(404);

        $this->assertSame(404, $response->getStatus());
        $this->assertSame('Not Found', $response->getReason());
    }

    public function testHandleErrorUsesCustomReason(): void
    {
        $response = new ErrorHandler()->handleError(500, 'Something broke');

        $this->assertSame('Something broke', $response->getReason());
    }

    public function testHandleErrorSetsJsonContentType(): void
    {
        $response = new ErrorHandler()->handleError(400);

        $this->assertSame('application/json', $response->getHeader('content-type'));
    }

    public function testHandleErrorEncodesStatusAndReasonAsJsonBody(): void
    {
        $response = new ErrorHandler()->handleError(404);

        $this->assertSame(
            ['status' => 404, 'message' => 'Not Found'],
            \json_decode(buffer($response->getBody()), true, flags: JSON_THROW_ON_ERROR),
        );
    }
}
