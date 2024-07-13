<?php declare(strict_types=1);

namespace Lav45\MockServer\Domain\Factory\Response;

use Lav45\MockServer\Domain\ValueObject\Response\Url as UrlResponse;

final readonly class Url
{
    public function __construct(private string $url) {}

    public function withQuery(array $get): self
    {
        $url = $this->appendQuery($this->url, $get);
        return new self($url);
    }

    public function create(): UrlResponse
    {
        return new UrlResponse($this->url);
    }

    private function appendQuery(string $url, array $get): string
    {
        if (empty($get)) {
            return $url;
        }

        $query = $oldQuery = \parse_url($url, PHP_URL_QUERY);
        $query = $this->parseQuery($query) + $get;
        $query = \http_build_query($query);

        if (\str_contains($url, '?')) {
            $url = \str_replace("?{$oldQuery}", "?{$query}", $url);
        } else {
            $url .= "?{$query}";
        }

        return $url;
    }

    private function parseQuery(string|null $query): array
    {
        if (empty($query)) {
            return [];
        }
        \parse_str($query, $parseQuery);
        return $parseQuery;
    }
}
