<?php declare(strict_types=1);

namespace Lav45\MockServer\Infrastructure\Component;

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

    public static function map(array $data, \Closure $fn): array
    {
        return self::mapTail($data, $fn);
    }

    private static function mapTail(array $data, \Closure $fn, array $result = [], array|null $keys = null): array
    {
        if ($keys === null) {
            $keys = \array_keys($data);
        }
        if (empty($keys)) {
            return $result;
        }

        $key = \array_shift($keys);
        $value = $data[$key];

        if (\is_array($value)) {
            $result[$key] = self::mapTail($value, $fn);
        } elseif (\is_string($value)) {
            $result[$key] = $fn($value);
        } else {
            $result[$key] = $value;
        }
        return self::mapTail($data, $fn, $result, $keys);
    }
}
