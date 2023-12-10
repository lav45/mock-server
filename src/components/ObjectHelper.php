<?php declare(strict_types=1);

namespace lav45\MockServer\components;

class ObjectHelper
{
    public static function configure(object $object, array $properties): void
    {
        foreach ($properties as $name => $value) {
            $object->$name = $value;
        }
    }
}