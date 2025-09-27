<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Parser;

final readonly class DateParse implements InlineParser
{
    private ParserHelper $parser;

    public function __construct(
        private InlineParser $inlineParser,
    ) {
        $this->parser = new ParserHelper('date\.(\w+)(\([^)]*\))');
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
        $now = \microtime(true);
        $now = \number_format($now, 6, '.', '');
        $now = \DateTime::createFromFormat('U.u', $now);

        $fn = [$now, $matches[2]];
        $arguments = isset($matches[3]) ? $this->parseArgs($matches[3]) : [];

        return \call_user_func_array($fn, $arguments);
    }

    private function parseArgs(string $str): array
    {
        $args = '[' . \substr($str, 1, -1) . ']';
        $args = \str_replace(["'", '\\'], ['"', '\\\\'], $args);
        return \json_decode($args, true, 512, JSON_THROW_ON_ERROR);
    }
}
