<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Components;

use Lav45\MockServer\Engine\Http\ServerRequest;

final class FakeServerRequest implements ServerRequest
{
    /** @var array<string, mixed> */
    private array $attributes = [];

    private string $path;

    /** @var array<string, list<string>> */
    private array $query;

    /** @var array<string, list<string>> */
    private array $headers;

    /**
     * @param array<string, list<string>|string> $headers
     * @param array<string, mixed> $parsedBody
     */
    public function __construct(
        private readonly string $method = 'GET',
        string                  $url = 'https://localhost/',
        array                   $headers = [],
        private readonly string $body = '',
        private readonly array  $parsedBody = [],
    ) {
        $parts = \parse_url($url);
        $this->path = $parts['path'] ?? '/';
        $this->query = $this->parseQuery($parts['query'] ?? '');
        $this->headers = $this->toLists($headers);
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getQueryParameters(): array
    {
        return $this->query;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getHeader(string $name): string|null
    {
        return $this->headers[\strtolower($name)][0] ?? null;
    }

    public function getParsedBody(): array
    {
        return $this->parsedBody;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function setAttribute(string $name, mixed $value): void
    {
        $this->attributes[$name] = $value;
    }

    public function getAttribute(string $name): mixed
    {
        return $this->attributes[$name] ?? null;
    }

    public function hasAttribute(string $name): bool
    {
        return \array_key_exists($name, $this->attributes);
    }

    /**
     * @return array<string, list<string>>
     */
    private function parseQuery(string $query): array
    {
        $result = [];
        foreach (\explode('&', $query) as $pair) {
            if ($pair === '') {
                continue;
            }
            [$key, $value] = \array_pad(\explode('=', $pair, 2), 2, '');
            $result[\urldecode($key)][] = \urldecode($value);
        }
        return $result;
    }

    /**
     * @param array<string, list<string>|string> $values
     * @return array<string, list<string>>
     */
    private function toLists(array $values): array
    {
        $result = [];
        foreach ($values as $key => $value) {
            $result[\strtolower($key)] = \is_array($value) ? \array_values($value) : [$value];
        }
        return $result;
    }
}
