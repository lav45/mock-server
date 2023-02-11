<?php

namespace lav45\MockServer\mock;

/**
 * Class RequestMock
 * @package lav45\MockServer
 */
class RequestMock extends DTObject
{
    /** @var string|array */
    public $method = 'GET';
    /** @var string */
    public string $url = '/';
}