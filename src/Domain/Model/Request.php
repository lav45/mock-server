<?php declare(strict_types=1);

namespace Lav45\MockServer\Domain\Model;

final readonly class Request
{
    public function __construct(
        public string|array $methods,
        public string       $url,
    ) {
        foreach ((array)$methods as $method) {
            $this->isValidMethod($method) ||  throw new \InvalidArgumentException("'{$method}' is invalid request method!");
        }
    }

    private function isValidMethod(string $value): bool
    {
        return (bool)\preg_match('/^[A-Z]+$/', $value);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            methods: $data['method'] ?? ['GET'],
            url: $data['url'] ?? '/',
        );
    }
}
