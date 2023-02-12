<?php

namespace lav45\MockServer\mock;

use Amp\Http\HttpStatus;
use lav45\MockServer\components\DTObject;

/**
 * Class MockResponseContent
 * @package lav45\MockServer\mock
 */
class MockResponseContent extends DTObject
{
    /** @var int */
    public int $status = HttpStatus::OK;
    /** @var array */
    public array $headers = [];
    /** @var string */
    public $text = '';

    /**
     * @param array $data
     * @throws \JsonException
     * @throws InvalidConfigException
     */
    public function setJson(array $data)
    {
        if ($this->text) {
            throw new InvalidConfigException("You can't use `text` and `json` at the same time");
        }
        $this->text = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}