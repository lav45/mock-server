<?php

namespace lav45\MockServer\mock;

use lav45\MockServer\components\DTObject;

/**
 * Class MockWebhook
 * @package lav45\MockServer\mock
 */
class MockWebhook extends DTObject
{
    /** @var float Number of seconds to wait. */
    public float $delay = 0;
    /** @var string */
    public string $method = 'POST';
    /** @var string */
    public $url;
    /**
     * @var array - guzzle request options
     * @see https://docs.guzzlephp.org/en/stable/request-options.html
     */
    public array $options = [];
}