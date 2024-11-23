<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Parser;

use Lav45\MockServer\Infrastructure\Component\ArrayHelper;

final readonly class ParamParser implements Parser
{
    private ParserHelper $parser;

    public function __construct(private array $data)
    {
        $this->parser = new ParserHelper(
            pattern: '([.\w]+)',
            value: fn(array $matches) => $this->getValue($matches),
        );
    }

    public function withData(array $data): self
    {
        $data = \array_merge_recursive($this->data, $data);
        return new self($data);
    }

    public function replace(mixed $data): mixed
    {
        return $this->parser->replace($data);
    }

    private function getValue(array $matches): mixed
    {
        return ArrayHelper::getValue($this->data, $matches[2], $matches[1]);
    }
}
