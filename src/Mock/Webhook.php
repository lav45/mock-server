<?php

namespace lav45\MockServer\Mock;

use lav45\MockServer\components\DTObject;

/**
 * Class Webhook
 * @package lav45\MockServer\Mock
 */
class Webhook extends DTObject
{
    /** @var float|string Number of seconds to wait. */
    public $delay = 0;
    /** @var string */
    public string $method = 'POST';
    /** @var string */
    public string $url;
    /**
     * @var array - guzzle request options
     * @see https://docs.guzzlephp.org/en/stable/request-options.html
     */
    public array $options = [];
}