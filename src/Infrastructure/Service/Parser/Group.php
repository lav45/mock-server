<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Service\Parser;

use Lav45\MockServer\Domain\Service\Parser;

final readonly class Group implements Parser
{
    /** @var Parser[] */
    private array $parsers;

    public function __construct(Parser ...$parser)
    {
        $this->parsers = $parser;
    }

    public function replace(mixed $data): mixed
    {
        foreach ($this->parsers as $parser) {
            $data = $parser->replace($data);
        }
        return $data;
    }

    public function withData(array $data): self
    {
        $items = [];
        foreach ($this->parsers as $parser) {
            $items[] = $parser->withData($data);
        }
        return new self(...$items);
    }
}
