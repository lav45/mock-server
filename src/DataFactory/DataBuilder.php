<?php declare(strict_types=1);

namespace Lav45\MockServer\DataFactory;

use Lav45\MockServer\Domain\ValueObject\Body;
use Lav45\MockServer\Domain\ValueObject\Delay;
use Lav45\MockServer\Domain\ValueObject\HttpHeaders;
use Lav45\MockServer\Domain\ValueObject\HttpMethod;
use Lav45\MockServer\Domain\ValueObject\HttpStatus;
use Lav45\MockServer\Domain\ValueObject\Url;
use Lav45\MockServer\Parser\InlineParser;

final class DataBuilder
{
    private InlineParser|null $parser = null;

    private array $data = [];

    public function __construct(
        private readonly array $filterHeaders = [],
    ) {}

    public function withParser(InlineParser $parser): self
    {
        return clone($this, [
            'parser' => $parser,
        ]);
    }

    public function withData(array $data): self
    {
        return clone($this, [
            'data' => $data,
        ]);
    }

    private function resolve(mixed $value): mixed
    {
        if ($this->parser === null) {
            return $value;
        }
        return $this->parser->replace($value);
    }

    public function createDelay(): Delay
    {
        if (isset($this->data['delay'])) {
            $value = $this->data['delay'];
            if (\is_string($value)) {
                $value = (float)$this->resolve($value);
            }
        } else {
            $value = 0.0;
        }
        return new Delay($value);
    }

    public function createStatus(): HttpStatus
    {
        if (isset($this->data['status'])) {
            $value = $this->data['status'];
            if (\is_string($value)) {
                $value = (int)$this->resolve($value);
            }
        } else {
            $value = 200;
        }
        return new HttpStatus($value);
    }

    public function createHeaders(array $appendHeaders = []): HttpHeaders
    {
        if (isset($this->data['headers'])) {
            $headers = $this->data['headers'];
            if ($headers) {
                $headers = $this->resolve($headers);
            }
        } else {
            $headers = [];
        }
        if ($appendHeaders) {
            foreach ($appendHeaders as $name => $value) {
                if (isset($headers[$name]) === false
                    && \in_array($name, $this->filterHeaders, true) === false
                ) {
                    $headers[$name] = $value;
                }
            }
        }
        return HttpHeaders::fromArray($headers);
    }

    public function createBodyContent(): Body|null
    {
        if (isset($this->data['content']) === false) {
            return null;
        }
        return Body::new(
            $this->resolve(
                $this->data['content'],
            ),
        );
    }

    public function createBody(): Body
    {
        if (isset($this->data['body'])) {
            $body = $this->resolve($this->data['body']);
        } else {
            $body = '';
        }
        return Body::new($body);
    }

    public function createUrl(array $get = []): Url
    {
        if (isset($this->data['url'])) {
            $value = $this->resolve($this->data['url']);
        } else {
            $value = '';
        }
        return new Url(
            $this->appendQuery($value, $get),
        );
    }

    private function appendQuery(string $url, array $get): string
    {
        if (empty($get)) {
            return $url;
        }
        $query = $oldQuery = \parse_url($url, PHP_URL_QUERY);
        if ($query) {
            \parse_str($query, $parseQuery);
            $query = $parseQuery + $get;
        } else {
            $query = $get;
        }
        $query = \http_build_query($query);

        if (\str_contains($url, '?')) {
            $url = \str_replace("?{$oldQuery}", "?{$query}", $url);
        } else {
            $url .= "?{$query}";
        }
        return $url;
    }

    public function createItems(): array
    {
        if (isset($this->data['file'])) {
            $content = \file_get_contents(
                $this->resolve($this->data['file']),
            );
            $items = \json_decode($content, associative: true, flags: JSON_THROW_ON_ERROR);
        } else {
            $items = $this->data['items'] ?? [];
        }
        return $this->resolve($items);
    }

    public function createMethod(): HttpMethod
    {
        if (isset($this->data['method'])) {
            $value = \strtoupper(
                $this->resolve($this->data['method']),
            );
        } else {
            $value = 'POST';
        }
        return new HttpMethod($value);
    }
}
