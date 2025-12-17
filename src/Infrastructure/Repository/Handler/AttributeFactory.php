<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Repository\Handler;

use Lav45\MockServer\Domain\Model\Response\Body;
use Lav45\MockServer\Domain\Model\Response\Delay;
use Lav45\MockServer\Domain\Model\Response\HttpHeader;
use Lav45\MockServer\Domain\Model\Response\HttpHeaders;
use Lav45\MockServer\Domain\Model\Response\HttpMethod;
use Lav45\MockServer\Domain\Model\Response\HttpStatus;
use Lav45\MockServer\Domain\Model\Response\Url;
use Lav45\MockServer\Infrastructure\Parser\DataParser;

use function Amp\File\read;

final readonly class AttributeFactory
{
    public function __construct(
        private DataParser $parser,
        private array      $data,
    ) {}

    public function createDelay(): Delay
    {
        $delay = $this->data['delay'] ?? 0.0 |> $this->parser->replace(...);
        return new Delay((float)$delay);
    }

    public function createStatus(): HttpStatus
    {
        $value = $this->data['status'] ?? 200 |> $this->parser->replace(...);
        return new HttpStatus((int)$value);
    }

    public function createHeaders(bool $withJson = false, array $appendHeaders = []): HttpHeaders
    {
        $headers = $this->parser->replace(
            $this->data['headers'] ?? [],
        );
        if ($appendHeaders) {
            unset(
                $appendHeaders['host'],
                $appendHeaders['content-length'],
            );
            $headers += \array_map(static fn($value) => $value[0], $appendHeaders);
        }
        if ($withJson) {
            $headers['content-type'] = 'application/json';
        }

        $result = [];
        /** @var array<string,string|int> $headers */
        foreach ($headers as $name => $value) {
            $result[] = new HttpHeader($name, (string)$value);
        }
        return new HttpHeaders(...$result);
    }

    public function createBody(): Body
    {
        return Body::new(
            $this->parser->replace(
                $this->data['content'] ?? '',
            ),
        );
    }

    public function createBodyContent(): Body
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
        $url = $this->parser->replace(
            $this->data['url'] ?? '',
        );
        return new Url(
            $this->appendQuery($url, $get),
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
            $items = \json_decode(
                read($this->data['file']),
                associative: true,
                flags: JSON_THROW_ON_ERROR,
            );
        } else {
            $items = $this->data['json'] ?? [];
        }
        return $this->parser->replace($items);
    }

    public function createMethod(): HttpMethod
    {
        $method = $this->data['method'] ?? 'POST'
            |> $this->parser->replace(...)
            |> \strtoupper(...);
        return new HttpMethod($method);
    }
}
