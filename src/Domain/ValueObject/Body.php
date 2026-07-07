<?php declare(strict_types=1);

namespace Lav45\MockServer\Domain\ValueObject;

final readonly class Body
{
    private function __construct(
        public Stream $stream,
    ) {}

    public static function new(string|array|Stream $data): self
    {
        if (\is_array($data)) {
            $data = \json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        if (\is_string($data)) {
            $data = new StringStream($data);
        }
        return new self($data);
    }
}
