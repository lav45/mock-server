<?php

namespace lav45\MockServer\mock;

use Amp\Http\HttpStatus;

/**
 * Class ResponseMock
 * @package lav45\MockServer
 */
class ResponseMock extends DTObject
{
    /** @var int */
    public int $status = HttpStatus::OK;
    /** @var array */
    public array $headers = [];
    /** @var string */
    public $proxyUrl;
    /** @var array */
    public array $options = [];
    /** @var string|array */
    private $body = '';

    /**
     * @return string
     * @throws \JsonException
     */
    public function getBody(): string
    {
        if (is_string($this->body)) {
            return $this->body;
        }
        return json_encode($this->body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param array|string $body
     */
    public function setBody($body)
    {
        $this->body = $body;
    }
}