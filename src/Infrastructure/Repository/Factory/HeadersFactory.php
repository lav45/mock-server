<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Repository\Factory;

use Lav45\MockServer\Domain\Model\Response\HttpHeader;
use Lav45\MockServer\Domain\Model\Response\HttpHeaders;
use Lav45\MockServer\Infrastructure\Component\ArrayHelper;
use Lav45\MockServer\Infrastructure\Parser\Parser;
use Psr\Log\LoggerInterface;

final readonly class HeadersFactory
{
    public function __construct(
        private Parser          $parser,
        private LoggerInterface $logger,
        private bool            $withJson = false,
        private array           $appendHeaders = [],
    ) {}

    public function create(array $data, string $path, null|string $optionPath = null): HttpHeaders
    {
        $headers = null;
        if ($optionPath) {
            $headers = ArrayHelper::getValue($data, $optionPath);
            if ($headers !== null) {
                $this->logger->info("Data:\n" . \json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
                $this->logger->warning("Option '{$optionPath}' is deprecated, you can use '{$path}' or run `upgrade` script.");
            }
        }
        $headers ??= ArrayHelper::getValue($data, $path, []);
        $headers = $this->parser->replace($headers);

        if ($this->appendHeaders) {
            $headers += $this->filterHeaders($this->appendHeaders);
        }
        if ($this->withJson) {
            $headers['content-type'] = 'application/json';
        }

        $result = [];
        /** @var array<string,string|int> $headers */
        foreach ($headers as $name => $value) {
            $result[] = new HttpHeader($name, (string)$value);
        }
        return new HttpHeaders(...$result);
    }

    private function filterHeaders(array $headers): array
    {
        $result = [];
        foreach ($headers as $key => $value) {
            if ($key === 'host' || $key === 'content-length') {
                continue;
            }
            $result[$key] = $value[0];
        }
        return $result;
    }
}
