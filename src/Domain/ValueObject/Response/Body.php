<?php declare(strict_types=1);

namespace Lav45\MockServer\Domain\ValueObject\Response;

use Stringable;

final readonly class Body implements Stringable
{
    private function __construct(public string $content) {}

    public function __toString(): string
    {
        return $this->toString();
    }

    public function toString(): string
    {
        return $this->content;
    }

    public function toArray(): array
    {
        if ($this->content) {
            return \json_decode($this->content, true, 512, JSON_THROW_ON_ERROR);
        }
        return [];
    }

    public static function new(array|string|null $data = null): self
    {
        return \is_array($data) ?
            self::fromJson($data) :
            self::fromText($data);
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

    public static function from(
        array|null  $json = null,
        string|null $text = null,
        string|null $file = null,
    ): self {
        if ($file) {
            return self::fromJsonFile($file);
        }
        if ($json) {
            return self::fromJson($json);
        }
        return self::fromText($text);
    }

    public static function fromJsonFile(string $file): self
    {
        $content = \file_get_contents($file);
        \assert(\json_validate($content), 'Invalid file content');
        return new self($content);
    }
}
