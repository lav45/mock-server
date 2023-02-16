<?php

namespace lav45\MockServer\mock;

use lav45\MockServer\components\DTObject;
use lav45\MockServer\InvalidConfigException;

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
    /** @var array */
    private array $json = [];

    /**
     * @return array
     */
    public function getJson(): array
    {
        return $this->json;
    }

    /**
     * @param array $data
     * @throws InvalidConfigException
     */
    public function setJson(array $data)
    {
        if ($this->text) {
            throw new InvalidConfigException("You can't use `text` and `json` at the same time");
        }
        if (empty($data)) {
            $this->setAsText($data);
        }
        $this->json = $data;
        $this->addHeader('content-type', 'application/json');
    }

    /**
     * @param array $data
     * @throws \JsonException
     */
    public function setAsText(array $data)
    {
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
        $this->headers = [];
        foreach ($headers as $key => $value) {
            $this->addHeader($key, $value);
        }
    }

    /**
     * @param string $key
     * @param string $value
     */
    protected function addHeader($key, $value)
    {
        $this->headers[strtolower($key)] = strtolower($value);
    }
}