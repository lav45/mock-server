<?php

namespace lav45\MockServer\mock;

use lav45\MockServer\components\DTObject;

/**
 * Class MockRequest
 * @package lav45\MockServer\mock
 */
class MockRequest extends DTObject
{
    /** @var string|array */
    public $method = ['GET'];
    /** @var string */
    public string $url = '/';
}