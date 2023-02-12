<?php

namespace lav45\MockServer\mock;

use lav45\MockServer\components\DTObject;

/**
 * Class MockResponseProxy
 * @package lav45\MockServer\mock
 */
class MockResponseProxy extends DTObject
{
    /** @var string */
    public $url;
    /**
     * @var array - guzzle request options
     * @see https://docs.guzzlephp.org/en/stable/request-options.html
     */
    public array $options = [];
}