<?php declare(strict_types=1);

namespace lav45\MockServer\Domain\Factory\Response;

use lav45\MockServer\Domain\Service\Parser;
use lav45\MockServer\Domain\ValueObject\Response\HttpHeader;
use lav45\MockServer\Domain\ValueObject\Response\HttpHeaders as HttpHeadersResponse;

final readonly class HttpHeaders
{
    public function __construct(
        private Parser $parser,
        /** @var array<string,string> */
        private array  $headers,
        private bool   $withJson = false
    )
    {
    }

    public static function new(Parser $parser, array $headers, bool $withJson = false): HttpHeadersResponse
    {
        return (new self($parser, $headers, $withJson))->create();
    }

    public function withData(array $data): self
    {
        $data = $this->parser->replace($data);
        $parser = $this->parser->withData($data);
        return new self($parser, $this->headers, $this->withJson);
    }

    public function create(): HttpHeadersResponse
    {
        $headers = [];
        if ($this->withJson) {
            $headers[] = new HttpHeader('content-type', 'application/json');
        }
        foreach ($this->parser->replace($this->headers) as $name => $value) {
            $headers[] = new HttpHeader($name, (string)$value);
        }
        return new HttpHeadersResponse(...$headers);
    }
}