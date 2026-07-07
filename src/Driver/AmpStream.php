<?php declare(strict_types=1);

namespace Lav45\MockServer\Driver;

use Amp\ByteStream\ReadableStream;
use Lav45\MockServer\Domain\ValueObject\Stream;

use function Amp\ByteStream\buffer;

final readonly class AmpStream implements Stream
{
    public function __construct(
        private ReadableStream $stream,
    ) {}

    public function read(): string
    {
        return buffer($this->stream);
    }

    public function getStream(): ReadableStream
    {
        return $this->stream;
    }
}
