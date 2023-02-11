<?php

namespace lav45\MockServer\mock;

/**
 * Class WebhookMock
 * @package lav45\MockServer
 */
class WebhookMock extends DTObject
{
    /** @var float Number of seconds to wait. */
    public float $delay = 0;
    /** @var string */
    public string $method = 'POST';
    /** @var string */
    public $url;
    /** @var array */
    public array $options = [];
}