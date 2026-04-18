<?php declare(strict_types=1);

namespace Lav45\MockServer\Domain\Request;

final readonly class Url
{
    public function __construct(public string $value)
    {
        $this->isValidUrl($value) || throw new \InvalidArgumentException('Invalid url: "' . $value . '"');
    }

    private function isValidUrl(string $value): bool
    {
        return $value !== '' && $value[0] === '/';
    }
}
