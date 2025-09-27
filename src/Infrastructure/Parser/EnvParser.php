<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Parser;

final readonly class EnvParser implements InlineParser
{
    private ParserHelper $parser;

    public function __construct(
        private InlineParser $inlineParser,
    ) {
        $this->parser = new ParserHelper('env\.([.\w]+)');
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
        if ($value = \getenv($matches[2])) {
            return $value;
        }
        return $matches[1];
    }
}
