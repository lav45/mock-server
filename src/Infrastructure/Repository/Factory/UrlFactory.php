<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Repository\Factory;

use Lav45\MockServer\Domain\Model\Response\Url;
use Lav45\MockServer\Infrastructure\Component\ArrayHelper;
use Lav45\MockServer\Infrastructure\Parser\Parser;

final readonly class UrlFactory
{
    public function __construct(
        private Parser $parser,
    ) {}

    public function create(array $data, string $path, array $get = []): Url
    {
        $url = ArrayHelper::getValue($data, $path);
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
}
