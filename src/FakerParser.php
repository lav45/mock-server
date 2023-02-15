<?php

namespace lav45\MockServer;

use DateTime;
use Faker\Generator;

/**
 * Class FakerParser
 * @package lav45\MockServer
 */
class FakerParser
{
    /**
     * @param Generator $faker
     */
    public function __construct(private readonly Generator $faker)
    {
    }

    /**
     * @param string $string
     * @return mixed
     * @throws InvalidConfigException
     */
    public function parse(string $string)
    {
        $matches = $this->parceLine($string);
        if (empty($matches)) {
            return $string;
        }

        $format = $matches[1];
        $arguments = isset($matches[2]) ? $this->parseArgs($matches[2]) : [];
        $result = $this->faker->format($format, $arguments);

        if ($result instanceof DateTime)  {
            if (empty($matches[4]) || empty($matches[5])) {
                throw new InvalidConfigException('Incorrect format. Use DateTime .method() for the convert DateTime object to scalar value.');
            }
            $func = [$result, $matches[4]];
            $args = $this->parseArgs($matches[5]);
            return call_user_func_array($func, $args);
        }
        return $result;
    }

    /**
     * @param string $value
     * @return array
     */
    protected function parceLine(string $value)
    {
        preg_match('/{{\s?faker\.(\w+)(\([^)]*\))?(\.(\w+)(\([^)]*\)))?\s?}}/u', $value, $matches);
        return $matches;
    }

    /**
     * @param string $str
     * @return mixed
     */
    protected function parseArgs(string $str)
    {
        $args = '[' . substr($str, 1, -1) . ']';
        $args = str_replace(["'", '\\'], ['"', '\\\\'], $args);
        return json_decode($args, true);
    }
}