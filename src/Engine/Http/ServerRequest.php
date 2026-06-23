<?php declare(strict_types=1);

namespace Lav45\MockServer\Engine\Http;

interface ServerRequest
{
    public function getMethod(): string;

    public function getPath(): string;

    /**
     * @return array<string, list<string>>
     */
    public function getQueryParameters(): array;

    /**
     * @return array<string, list<string>>
     */
    public function getHeaders(): array;

    public function getHeader(string $name): string|null;

    /**
     * Parsed form body (urlencoded or multipart), already decoded by the server.
     * @return array<string, mixed>
     */
    public function getParsedBody(): array;

    public function getBody(): string;

    public function setAttribute(string $name, mixed $value): void;

    public function getAttribute(string $name): mixed;

    public function hasAttribute(string $name): bool;
}
