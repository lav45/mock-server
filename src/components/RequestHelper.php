<?php

namespace lav45\MockServer\components;

use Amp\Http\Server\Request;
use lav45\MockServer\Router;

/**
 * Class RequestHelper
 * @package lav45\MockServer\components
 */
class RequestHelper
{
    /**
     * @param Request $request
     * @param string $text
     * @return string
     */
    public static function replaceAttributes(Request $request, string $text)
    {
        $args = $request->getAttribute(Router::class);
        foreach ($args as $key => $value) {
            $text = str_replace("{{$key}}", $value, $text);
        }
        return $text;
    }
}