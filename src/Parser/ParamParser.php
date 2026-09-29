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
        if (\is_array($data)) {
            return ArrayHelper::map($data, $this->replace(...));
        }

        $data = $this->inlineParser->replace($data);

        return $this->parser->replace(
            $data,
            fn(array $matches) => $this->getValue($matches),
        );
    }

    private function getValue(array $matches): mixed
    {
        return ArrayHelper::getValue($this->data, $matches[2], $matches[1]);
    }
}
