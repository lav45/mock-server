<?php

namespace lav45\MockServer\Mock;

use lav45\MockServer\components\DTObject;

/**
 * Class Request
 * @package lav45\MockServer\Mock
 */
class Request extends DTObject
{
    /** @var array */
    private array $method = ['GET'];
    /** @var string */
    public string $url = '/';

    /**
     * @return array
     */
    public function getMethod(): array
    {
        return $this->method;
    }

    /**
     * @param array|string $method
     */
    public function setMethod($method)
    {
        $this->method = (array)$method;
    }
}