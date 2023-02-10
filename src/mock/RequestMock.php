<?php

namespace lav45\MockServer\mock;

/**
 * Class RequestMock
 * @package lav45\MockServer
 */
class RequestMock extends DTObject
{
    /** @var string */
    public string $method = 'GET';
    /** @var string */
    public string $path = '/';
    /** @var array */
    public array $headers = ['content-type' => 'application/json'];
}