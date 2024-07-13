<?php declare(strict_types=1);

namespace Lav45\MockServer\Domain\ValueObject\Response;

final readonly class Url
{
    public function __construct(public string $value)
    {
        \assert($this->isValidUrl($value), 'Invalid url: "' . $value . '"');
    }

    private function isValidUrl(string $value): bool
    {
        return \filter_var($value, FILTER_VALIDATE_URL) !== false;
    }
}
