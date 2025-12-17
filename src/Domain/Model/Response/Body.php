<?php declare(strict_types=1);

namespace Lav45\MockServer\Domain\Model\Response;

final readonly class Body
{
    private function __construct(public string $value) {}

    public function toString(): string
    {
        return $this->value;
    }

    public static function new(array|string $data): self
    {
        return \is_array($data)
            ? self::fromJson($data)
            : self::fromText($data);
    }

    public static function fromText(string $content): self
    {
        return new self($content);
    }

    public static function fromJson(array $data): self
    {
        return new self(
            \json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        );
    }
}
