<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Component;

use Closure;

final class ArrayHelper
{
    public static function getValue(
        array  $data,
        string $name,
        mixed  $default = null,
    ): mixed {
        $path = \explode('.', string: \rtrim($name, '.'));

        foreach ($path as $step) {
            if (\is_array($data) === false || (isset($data[$step]) || \array_key_exists($step, $data)) === false) {
                return $default;
            }
            $data = &$data[$step];
        }
        return $data;
    }

    public static function map(array $data, Closure $fn): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            if (\is_array($value)) {
                $result[$key] = self::map($value, $fn);
            } elseif (\is_string($value)) {
                $result[$key] = $fn($value);
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }
}
