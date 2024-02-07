<?php declare(strict_types=1);

namespace lav45\MockServer;

use DateTime;
use Faker\Generator;

final readonly class FakerParser
{
    public function __construct(private Generator $faker)
    {
    }

    public function parse(string $string): mixed
    {
        $callback = function ($matches) {
            $format = $matches[1];
            $arguments = isset($matches[2]) ? $this->parseArgs($matches[2]) : [];
            $result = $this->faker->format($format, $arguments);

            if ($result instanceof DateTime) {
                $func = [$result, $matches[4]];
                $args = $this->parseArgs($matches[5]);
                return call_user_func_array($func, $args);
            }
            return $result;
        };

        $pattern = '\s?faker\.(\w+)(\([^)]*\))?(\.(\w+)(\([^)]*\)))?\s?';
        preg_match('/{{' .$pattern . '}}/u', $string, $matches);
        if ($matches) {
            return $callback($matches);
        }
        return preg_replace_callback('/{' . $pattern . '}/u', $callback, $string);
    }

    protected function parseArgs(string $str): array
    {
        $args = '[' . substr($str, 1, -1) . ']';
        $args = str_replace(["'", '\\'], ['"', '\\\\'], $args);
        return json_decode($args, true, 512, JSON_THROW_ON_ERROR);
    }
}