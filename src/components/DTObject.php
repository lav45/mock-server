<?php

namespace lav45\MockServer\components;

/**
 * Class DTObject
 * @package lav45\MockServer\components
 */
class DTObject
{
    /**
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        configure($this, $config);
    }

    /**
     * @param string $name
     * @return mixed|null
     */
    public function __get(string $name)
    {
        $getter = 'get' . $name;
        if (method_exists($this, $getter)) {
            return $this->$getter();
        }
        return null;
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function __set(string $name, $value)
    {
        $setter = 'set' . $name;
        if (method_exists($this, $setter)) {
            $this->$setter($value);
        }
    }

    public function __isset($name)
    {
        $getter = 'get' . $name;
        if (method_exists($this, $getter)) {
            return $this->$getter() !== null;
        }
        return false;
    }
}

function configure(object $object, array $properties)
{
    foreach ($properties as $name => $value) {
        $object->$name = $value;
    }
}