<?php declare(strict_types=1);

namespace Lav45\MockServer\Parser;

use Lav45\MockServer\Helper\ArrayHelper;

final class ParamParser implements VariableParser
{
    private BaseParser $parser;

    private array $data = [];

    public function __construct(
        private readonly InlineParser $inlineParser,
    ) {
        $this->parser = new BaseParser('([.\w-]+)');
    }

    public function withData(array $data): self
    {
        return clone($this, [
            'data' => \array_replace_recursive($this->data, $data),
        ]);
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
