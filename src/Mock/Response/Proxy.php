<?php

namespace lav45\MockServer\Mock\Response;

use lav45\MockServer\components\DTObject;

/**
 * Class Proxy
 * @package lav45\MockServer\Mock\Response
 */
class Proxy extends DTObject
{
    /** @var string */
    public $url;
    /**
     * @var array - guzzle request options
     * @see https://docs.guzzlephp.org/en/stable/request-options.html
     */
    public array $options = [];
}