<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Suite\Driver;

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
}
