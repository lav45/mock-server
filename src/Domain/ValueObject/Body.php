<?php declare(strict_types=1);

namespace Lav45\MockServer\Domain\ValueObject;

final readonly class Body
{
    private function __construct(
        private string|array $value,
    ) {}

    public function toString(): string
    {
        if (\is_array($this->value)) {
            return \json_encode($this->value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        return $this->value;
    }

    public static function new(array|string $data): self
    {
        return new self($data);
    }

    public function isJson(): bool
    {
        return \is_array($this->value) || \json_validate($this->value);
    }
}
