<?php declare(strict_types=1);

namespace Lav45\MockServer\Driver;

use Amp\Http\Server\FormParser;
use Amp\Http\Server\Request;
use Lav45\MockServer\Engine\Http\ServerRequest as ServerRequestInterface;

final class ServerRequest implements ServerRequestInterface
{
    private string|null $body = null;

    public function __construct(
        private readonly Request $request,
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
            $this->body = $this->request->getBody()->buffer();
        }
        return $this->body;
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
