<?php declare(strict_types=1);

namespace Lav45\MockServer\Driver;

use Amp\ByteStream\BufferException;
use Amp\Http\Server\FormParser;
use Amp\Http\Server\Request;

final class ServerRequest implements \Lav45\MockServer\Engine\Http\ServerRequest
{
    private string|null $body = null;

    public function __construct(
        private readonly Request $request,
        private readonly int     $maxBufferSize = \PHP_INT_MAX,
    ) {}

    public function getMethod(): string
    {
        return $this->request->getMethod();
    }

    public function getPath(): string
    {
        return $this->request->getUri()->getPath();
    }

    public function getQueryParameters(): array
    {
        return $this->request->getQueryParameters();
    }

    public function getHeaders(): array
    {
        return $this->request->getHeaders();
    }

    public function getHeader(string $name): string|null
    {
        return $this->request->getHeader($name);
    }

    public function getParsedBody(): array
    {
        $body = $this->getBody();
        if ($body === '') {
            return [];
        }
        $boundary = FormParser\parseContentBoundary(
            contentType: $this->request->getHeader('content-type') ?? '',
        );
        $values = new FormParser\FormParser()
            ->parseBody($body, $boundary)
            ->getValues();

        return $this->normalizeValues($values);
    }

    public function getBody(): string
    {
        if ($this->body === null) {
            try {
                $this->body = $this->request->getBody()->buffer(limit: $this->maxBufferSize);
            } catch (BufferException $exception) {
                throw new RequestBodyTooLargeException(
                    "Request body exceeds the limit of {$this->maxBufferSize} bytes",
                    previous: $exception,
                );
            }
        }
        return $this->body;
    }

    public function drainBody(): void
    {
        if ($this->body !== null) {
            return;
        }
        $this->request->getBody()->close();
    }

    public function setAttribute(string $name, mixed $value): void
    {
        $this->request->setAttribute($name, $value);
    }

    public function getAttribute(string $name): mixed
    {
        return $this->request->hasAttribute($name)
            ? $this->request->getAttribute($name)
            : null;
    }

    public function hasAttribute(string $name): bool
    {
        return $this->request->hasAttribute($name);
    }

    /**
     * @param array<string, list<string>> $values
     * @return array<string, mixed>
     */
    private function normalizeValues(array $values): array
    {
        $result = [];
        foreach ($values as $key => $value) {
            if (isset($value[1])) {
                $result[$key] = $value;
            } else {
                $result[$key] = $value[0];
            }
        }
        return $result;
    }
}
