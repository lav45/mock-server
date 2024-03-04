<?php declare(strict_types=1);

namespace lav45\MockServer\Infrastructure\Service\Parser;

use DateTime;
use Faker\Generator;
use lav45\MockServer\Domain\Service\Parser;
use lav45\MockServer\Infrastructure\Component\ArrayHelper;

final readonly class Faker implements Parser
{
    public function __construct(private Generator $faker)
    {
    }

    public function withData(array $data): self
    {
        return $this;
    }

    public function replace(mixed $data): mixed
    {
        if (is_string($data)) {
            return $this->replaceAttribute($data);
        }
        if (is_array($data)) {
            return $this->replaceMap($data);
        }
        return $data;
    }

    private function replaceMap(array $data): array
    {
        return ArrayHelper::map($data, fn($value) => $this->replaceAttribute($value));
    }

    private function replaceAttribute(string $string): mixed
    {
        $pattern = '\s*faker\.(\w+)(\([^)]*\))?(\.(\w+)(\([^)]*\)))?\s*';
        preg_match('/{{' . $pattern . '}}/iu', $string, $matches);
        if ($matches) {
            return $this->generate($matches);
        }
        return preg_replace_callback('/{' . $pattern . '}/iu', fn($matches) => $this->generate($matches), $string);
    }

    private function generate(array $matches): mixed
    {
        $format = $matches[1];
        $arguments = isset($matches[2]) ? $this->parseArgs($matches[2]) : [];
        $result = $this->faker->format($format, $arguments);

        if ($result instanceof DateTime) {
            $func = [$result, $matches[4]];
            $args = $this->parseArgs($matches[5]);
            return call_user_func_array($func, $args);
        }
        return $result;
    }

    private function parseArgs(string $str): array
    {
        $args = '[' . substr($str, 1, -1) . ']';
        $args = str_replace(["'", '\\'], ['"', '\\\\'], $args);
        return json_decode($args, true, 512, JSON_THROW_ON_ERROR);
    }
}