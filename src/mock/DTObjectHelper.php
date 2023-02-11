<?php

namespace lav45\MockServer\mock;

/**
 * Class DTObjectHelper
 * @package lav45\MockServer\mock
 */
class DTObjectHelper
{
    /**
     * @param object $object
     * @param array $properties
     */
    public static function configure(object $object, array $properties)
    {
        foreach ($properties as $name => $value) {
            $object->$name = $value;
        }
    }
}