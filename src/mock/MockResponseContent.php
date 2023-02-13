<?php

namespace lav45\MockServer\mock;

use lav45\MockServer\components\DTObject;

/**
 * Class MockResponseContent
 * @package lav45\MockServer\mock
 */
class MockResponseContent extends DTObject
{
    /** @var int */
    public int $status = 200;
    /** @var array */
    private array $headers = [];
    /** @var string */
    public string $text = '';

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
        $this->setHeaders(['content-type' => 'application/json']);
        $this->text = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @param array $headers
     */
    public function setHeaders(array $headers)
    {
        foreach ($headers as $key => $value) {
            $this->headers[strtolower($key)] = strtolower($value);
        }
    }
}