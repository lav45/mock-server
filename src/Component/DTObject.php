<?php declare(strict_types=1);

namespace lav45\MockServer\Component;

abstract class DTObject
{
    public function __construct(array $config = [])
    {
        ObjectHelper::configure($this, $config);
    }

    public function __get(string $name)
    {
        $getter = 'get' . $name;
        if (method_exists($this, $getter)) {
            return $this->$getter();
        }
        return null;
    }

    public function __set(string $name, mixed $value)
    {
        $setter = 'set' . $name;
        if (method_exists($this, $setter)) {
            $this->$setter($value);
        }
    }

    public function __isset(string $name)
    {
        $getter = 'get' . $name;
        if (method_exists($this, $getter)) {
            return $this->$getter() !== null;
        }
        return false;
    }
}