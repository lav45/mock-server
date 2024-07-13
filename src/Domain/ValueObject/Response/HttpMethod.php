<?php declare(strict_types=1);

namespace Lav45\MockServer\Domain\ValueObject\Response;

final readonly class HttpMethod
{
    public function __construct(public string $value)
    {
        \assert($this->isValidMethod($value), 'Invalid method');
    }

    public static function new(string $value): self
    {
        return new self(\strtoupper($value));
    }

    private function isValidMethod(string $value): bool
    {
        return (bool)\preg_match('/^[A-Z]+$/', $value);
    }
}
