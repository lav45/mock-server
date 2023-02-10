<?php

namespace lav45\MockServer\mock;

/**
 * Class WebhookMock
 * @package lav45\MockServer
 */
class WebhookMock extends DTObject
{
    /** @var int Number of seconds to wait. */
    public int $delay = 0;
    /** @var string */
    public string $method = 'POST';
    /** @var string */
    public $url;
    /** @var array */
    public array $options = [];
}