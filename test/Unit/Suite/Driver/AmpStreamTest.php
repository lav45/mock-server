<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Driver;

use Amp\ByteStream\BufferException;
use Amp\ByteStream\ReadableBuffer;
use Lav45\MockServer\Driver\AmpStream;
use PHPUnit\Framework\TestCase;

final class AmpStreamTest extends TestCase
{
    public function testReadBuffersUnderlyingStream(): void
    {
        $stream = new AmpStream(new ReadableBuffer('payload'));

        $this->assertSame('payload', $stream->read());
    }

    public function testGetStreamReturnsUnderlyingStream(): void
    {
        $readable = new ReadableBuffer('payload');
        $stream = new AmpStream($readable);

        $this->assertSame($readable, $stream->getStream());
    }

    public function testReadBuffersStreamWithinMaxBufferSize(): void
    {
        $stream = new AmpStream(new ReadableBuffer('payload'), maxBufferSize: 7);

        $this->assertSame('payload', $stream->read());
    }

    public function testReadThrowsWhenMaxBufferSizeExceeded(): void
    {
        $stream = new AmpStream(new ReadableBuffer('payload'), maxBufferSize: 4);

        $this->expectException(BufferException::class);
        $this->expectExceptionMessageIsOrContains('Buffer length limit of 4 bytes exceeded');

        $stream->read();
    }
}
