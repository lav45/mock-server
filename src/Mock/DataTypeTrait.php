<?php

namespace lav45\MockServer\Mock;

use lav45\MockServer\InvalidConfigException;

/**
 * Class DataTypeTrait
 * @package lav45\MockServer\Mock
 */
trait DataTypeTrait
{
    /** @var string|null */
    private $type;

    /**
     * @return string|null
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @throws InvalidConfigException
     */
    protected function setType(string $type)
    {
        if ($this->type) {
            throw new InvalidConfigException("You can't use `{$type}` and `{$this->type}` at the same time");
        }
        $this->type = $type;
    }
}