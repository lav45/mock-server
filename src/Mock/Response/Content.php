<?php

namespace lav45\MockServer\Mock\Response;

use lav45\MockServer\components\DTObject;
use lav45\MockServer\InvalidConfigException;
use lav45\MockServer\Mock\DataTypeTrait;

/**
 * Class Content
 * @package lav45\MockServer\Mock\Response
 */
class Content extends DTObject
{
    use DataTypeTrait;

    public const TYPE_JSON = 'json';
    public const TYPE_TEXT = 'text';

    /** @var int */
    public int $status = 200;
    /** @var array */
    private array $headers = [];
    /** @var string */
    private string $text = '';
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
        $this->setType(self::TYPE_JSON);
        $this->addHeader('content-type', 'application/json');
        $this->json = $data;
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

    /**
     * @return string
     */
    public function getText(): string
    {
        return $this->text;
    }

    /**
     * @param string $text
     * @throws InvalidConfigException
     */
    public function setText(string $text)
    {
        $this->setType(self::TYPE_TEXT);
        $this->text = $text;
    }
}