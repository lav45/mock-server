<?php

namespace lav45\MockServer\Mock\Response;

use lav45\MockServer\components\DTObject;
use lav45\MockServer\InvalidConfigException;
use lav45\MockServer\Mock\DataTypeTrait;
use lav45\MockServer\Mock\Response\Data\Pagination;

/**
 * Class Data
 * @package lav45\MockServer\Mock\Response
 */
class Data extends DTObject
{
    use DataTypeTrait;

    public const TYPE_JSON = 'json';
    public const TYPE_FILE = 'file';

    /** @var int */
    public int $status = 200;
    /** @var array */
    private array $headers = ['content-type' => 'application/json'];
    /** @var array */
    private array $json = [];
    /** @var Pagination */
    private Pagination $pagination;

    /**
     * @return Pagination
     */
    public function getPagination(): Pagination
    {
        return $this->pagination ??= new Pagination();
    }

    /**
     * @param array $pagination
     */
    public function setPagination(array $pagination)
    {
        $this->pagination = new Pagination($pagination);
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
     * @return array
     */
    public function getJson(): array
    {
        return $this->json;
    }

    /**
     * @param array $json
     * @throws InvalidConfigException
     */
    public function setJson(array $json)
    {
        $this->setType(self::TYPE_JSON);
        $this->json = $json;
    }

    /**
     * @param string $file
     * @throws InvalidConfigException
     * @throws \JsonException
     */
    public function setFile(string $file)
    {
        $this->setType(self::TYPE_FILE);
        $content = file_get_contents($file);
        $this->json = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    }
}