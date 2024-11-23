<?php declare(strict_types=1);

namespace Lav45\MockServer\Domain\Model\Response;

final readonly class HttpMethod
{
    public function __construct(public string $value)
    {
        \assert($this->isValidMethod($value), 'Invalid method');
    }

    private function isValidMethod(string $value): bool
    {
        return (bool)\preg_match('/^[A-Z]+$/', $value);
    }
}
