<?php declare(strict_types=1);

namespace Lav45\MockServer\Domain\Mock;

final readonly class Request
{
    public function __construct(
        public string|array $methods,
        public string       $url,
    ) {
        foreach ((array)$methods as $method) {
            $this->isValidMethod($method) || throw new \InvalidArgumentException("'{$method}' is invalid request method!");
        }
        $this->isValidUrl($url) || throw new \InvalidArgumentException("'{$url}' is invalid route pattern!");
    }

    private function isValidUrl(string $value): bool
    {
        return $value !== '' && $value[0] === '/';
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
