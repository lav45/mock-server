<?php declare(strict_types=1);

namespace Lav45\MockServer\Engine\Http;

use Lav45\MockServer\Domain\ValueObject\Body;

final readonly class ClientResponse
{
    /**
     * @param array<string, list<string>> $headers
     */
    public function __construct(
        private int    $status,
        private array  $headers,
        private Body $body,
    ) {}

    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @return array<string, list<string>>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getBody(): Body
    {
        return $this->body;
    }
}
