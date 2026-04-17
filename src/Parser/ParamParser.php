<?php declare(strict_types=1);

namespace Lav45\MockServer\Parser;

final class ParamParser implements VariableParser
{
    private BaseParser $parser;

    public array $data = [];

    public function __construct(
        private readonly InlineParser $inlineParser,
    ) {
        $this->parser = new BaseParser('([.\w]+)');
    }

    public function withData(array $data): self
    {
        $new = clone $this;
        $new->data = \array_merge_recursive($this->data, $data);
        return $new;
    }

    public function replace(mixed $data): mixed
    {
        return $this->parser->replace(
            $this->inlineParser->replace($data),
            fn(array $matches) => $this->getValue($matches),
        );
    }

    private function getValue(array $matches): mixed
    {
        return ArrayHelper::getValue($this->data, $matches[2], $matches[1]);
    }
}
