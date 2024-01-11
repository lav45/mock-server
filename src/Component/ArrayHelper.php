<?php declare(strict_types=1);

namespace lav45\MockServer\Component;

use function array_key_exists;
use function explode;
use function is_array;
use function rtrim;

class ArrayHelper
{
    public static function getValue(
        array  $data,
        string $name,
        mixed  $default = null
    ): mixed
    {
        $path = explode('.', string: rtrim($name, '.'));

        foreach ($path as $step) {
            if (is_array($data) === false || (isset($data[$step]) || array_key_exists($step, $data)) === false) {
                return $default;
            }
            $data = &$data[$step];
        }
        return $data;
    }
}