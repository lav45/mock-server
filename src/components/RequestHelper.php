<?php

namespace lav45\MockServer\components;

/**
 * Class RequestHelper
 * @package lav45\MockServer\components
 */
class RequestHelper
{
    /**
     * @param array $args
     * @param string $text
     * @param string|null $prefix
     * @return string
     */
    public static function replaceAttributes(array $args, string $text, string $prefix = null)
    {
        foreach ($args as $key => $value) {
            if ($prefix) {
                $key = "{$prefix}.{$key}";
            }
            $text = str_replace("{{$key}}", $value, $text);
        }
        return $text;
    }

    /**
     * @param array $vars
     * @param string $value
     * @param string $prefix
     * @return bool
     */
    public static function parceAttribute(array $vars, string &$value, string $prefix)
    {
        preg_match("/{{\s?{$prefix}\.(\w+)\s?}}/u", $value, $matches);
        if (empty($matches)) {
            return false;
        }
        $value = $vars[$matches[1]];
        return true;
    }
}