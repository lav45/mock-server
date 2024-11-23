<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Parser;

use DateTime;
use Faker\Generator;

final readonly class FakerParser implements Parser
{
    private ParserHelper $parser;

    public function __construct(private Generator $faker)
    {
        $this->parser = new ParserHelper(
            pattern: 'faker\.(\w+)(\([^)]*\))?(\.(\w+)(\([^)]*\)))?',
            value: fn(array $matches) => $this->getValue($matches),
        );
    }

    public function withData(array $data): self
    {
        return $this;
    }

    public function replace(mixed $data): mixed
    {
        return $this->parser->replace($data);
    }

    private function getValue(array $matches): mixed
    {
        $format = $matches[2];
        $arguments = isset($matches[3]) ? $this->parseArgs($matches[3]) : [];
        $result = $this->faker->format($format, $arguments);

        if ($result instanceof DateTime) {
            $func = [$result, $matches[5]];
            $args = $this->parseArgs($matches[6]);
            return \call_user_func_array($func, $args);
        }
        return $result;
    }

    private function parseArgs(string $str): array
    {
        $args = '[' . \substr($str, 1, -1) . ']';
        $args = \str_replace(["'", '\\'], ['"', '\\\\'], $args);
        return \json_decode($args, true, 512, JSON_THROW_ON_ERROR);
    }
}
