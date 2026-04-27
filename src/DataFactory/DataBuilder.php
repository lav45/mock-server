<?php declare(strict_types=1);

namespace Lav45\MockServer\DataFactory;

use Lav45\MockServer\Domain\ValueObject\Body;
use Lav45\MockServer\Domain\ValueObject\Delay;
use Lav45\MockServer\Domain\ValueObject\HttpHeaders;
use Lav45\MockServer\Domain\ValueObject\HttpMethod;
use Lav45\MockServer\Domain\ValueObject\HttpStatus;
use Lav45\MockServer\Domain\ValueObject\Url;
use Lav45\MockServer\Parser\VariableParser;

use function Amp\File\read;

final readonly class DataBuilder
{
    public function __construct(
        private VariableParser $parser,
        private array          $data,
    ) {}

    public function createDelay(): Delay
    {
        if (isset($this->data['delay'])) {
            $value = $this->data['delay'];
            if (\is_string($value)) {
                $value = (float)$this->parser->replace($value);
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
                $value = (int)$this->parser->replace($value);
            }
        } else {
            $value = 200;
        }
        return new HttpStatus($value);
    }

    public function createHeaders(bool $withJson = false, array $appendHeaders = []): HttpHeaders
    {
        if (isset($this->data['headers'])) {
            $headers = $this->data['headers'];
            if ($headers) {
                $headers = $this->parser->replace($headers);
            }
        } else {
            $headers = [];
        }
        if ($appendHeaders) {
            $filterHeaders = [
                'host',
                'content-length',
                'connection',
                'keep-alive',
                'transfer-encoding',
            ];
            foreach ($appendHeaders as $name => $value) {
                if (\in_array($name, $filterHeaders, true) === false) {
                    $headers[$name] = $value;
                }
            }
        }
        if ($withJson) {
            $headers['content-type'] = 'application/json';
        }
        return HttpHeaders::fromArray($headers);
    }

    public function createBodyContent(): Body
    {
        if (isset($this->data['content'])) {
            return Body::new(
                $this->parser->replace(
                    $this->data['content'],
                ),
            );
        }
        return Body::fromText('');
    }

    public function createBody(): Body
    {
        if (isset($this->data['json'])) {
            return Body::fromJson(
                $this->parser->replace(
                    $this->data['json'],
                ),
            );
        }
        if (isset($this->data['text'])) {
            return Body::fromText(
                $this->parser->replace(
                    $this->data['text'],
                ),
            );
        }
        return Body::fromText('');
    }

    public function createUrl(array $get = []): Url
    {
        if (isset($this->data['url'])) {
            $value = $this->parser->replace($this->data['url']);
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
            $content = read(
                $this->parser->replace($this->data['file']),
            );
            $items = \json_decode($content, associative: true, flags: JSON_THROW_ON_ERROR);
        } else {
            $items = $this->data['json'] ?? [];
        }
        return $this->parser->replace($items);
    }

    public function createMethod(): HttpMethod
    {
        if (isset($this->data['method'])) {
            $value = \strtoupper(
                $this->parser->replace($this->data['method']),
            );
        } else {
            $value = 'POST';
        }
        return new HttpMethod($value);
    }
}
