<?php declare(strict_types=1);

namespace Lav45\MockServer\Domain\Model\Response;

final readonly class Body
{
    private function __construct(public string $value) {}

    public function toString(): string
    {
        return $this->value;
    }

    public static function new(array|string|null $data = null): self
    {
        return \is_array($data)
            ? self::fromJson($data)
            : self::fromText($data);
    }

    public static function fromText(string|null $content): self
    {
        return new self($content ?? '');
    }

    public static function fromJson(array $data): self
    {
        $content = \json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return new self($content);
    }
}
