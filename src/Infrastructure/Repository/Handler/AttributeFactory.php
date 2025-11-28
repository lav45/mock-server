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
        $delay = $this->data['delay'] ?? 0.0;
        $delay = $this->parser->replace($delay);
        return new Delay((float)$delay);
    }

    public function createStatus(): HttpStatus
    {
        $value = $this->data['status'] ?? 200;
        $value = (int)$this->parser->replace($value);
        return new HttpStatus($value);
    }

    public function createHeaders(bool $withJson = false, array $appendHeaders = []): HttpHeaders
    {
        $headers = $this->data['headers'] ?? [];
        $headers = $this->parser->replace($headers);

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
        $value = $this->data['content'] ?? null;
        $value = $this->parser->replace($value);
        return Body::new($value);
    }

    public function createBodyContent(): Body
    {
        if (isset($this->data['json'])) {
            $value = $this->data['json'];
            $value = $this->parser->replace($value);
            return Body::fromJson($value);
        }

        $value = $this->data['text'] ?? null;
        $value = $this->parser->replace($value);
        return Body::fromText($value);
    }

    public function createUrl(array $get = []): Url
    {
        $url = $this->data['url'] ?? null;
        $url = $this->parser->replace($url);
        $url = $this->appendQuery($url, $get);
        return new Url($url);
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
        $file = $this->data['file'] ?? null;
        if ($file !== null) {
            $items = \json_decode(read($file), true, flags: JSON_THROW_ON_ERROR);
        } else {
            $items = $this->data['json'] ?? [];
        }
        return $this->parser->replace($items);
    }

    public function createMethod(): HttpMethod
    {
        $method = $this->data['method'] ?? 'POST';
        $method = $this->parser->replace($method);
        $method = \strtoupper($method);
        return new HttpMethod($method);
    }
}
