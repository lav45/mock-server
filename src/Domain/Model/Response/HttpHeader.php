<?php declare(strict_types=1);

namespace Lav45\MockServer\Domain\Model\Response;

final readonly class HttpHeader
{
    public function __construct(
        public string $name,
        public string $value,
    ) {
        $this->isValidName($name) || throw new \InvalidArgumentException('Invalid header name: "' . $name . '"');
        $this->isValidValue($value) || throw new \InvalidArgumentException('Invalid header value: "' . $value . '"');
    }

    private function isValidName(string $name): bool
    {
        return (bool)\preg_match('/^[a-z0-9`~!#$%^&_|\'\-*+.]+$/i', $name);
    }

    private function isValidValue(string $value): bool
    {
        return (bool)\preg_match('/[^\t\r\n\x20-\x7e\x80-\xfe]|\r\n/', $value) === false;
    }
}
