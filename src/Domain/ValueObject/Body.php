<?php declare(strict_types=1);

namespace Lav45\MockServer\Domain\ValueObject;

final readonly class Body
{
    private function __construct(public string $value) {}

    public function toString(): string
    {
        return $this->value;
    }

    public static function new(array|string $data): self
    {
        if (\is_array($data)) {
            $data = \json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        return new self($data);
    }
}
