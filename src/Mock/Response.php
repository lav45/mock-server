<?php

namespace lav45\MockServer\Mock;

use lav45\MockServer\components\DTObject;
use lav45\MockServer\InvalidConfigException;
use lav45\MockServer\Mock\Response\Content;
use lav45\MockServer\Mock\Response\Data;
use lav45\MockServer\Mock\Response\Proxy;

/**
 * Class Response
 * @package lav45\MockServer\Mock
 */
class Response extends DTObject
{
    use DataTypeTrait;

    public const TYPE_CONTENT = 'content';
    public const TYPE_PROXY = 'proxy';
    public const TYPE_DATA = 'data';

    /** @var float Number of seconds to wait. */
    public float $delay = 0;
    /** @var Content */
    private Content $content;
    /** @var Proxy */
    private Proxy $proxy;
    /** @var Data */
    private Data $data;

    /**
     * @return Proxy
     */
    public function getProxy(): Proxy
    {
        return $this->proxy ??= new Proxy();
    }

    /**
     * @param array $proxy
     * @throws InvalidConfigException
     */
    public function setProxy(array $proxy)
    {
        $this->setType(self::TYPE_PROXY);
        $this->proxy = new Proxy($proxy);
    }

    /**
     * @return Content
     */
    public function getContent(): Content
    {
        return $this->content ??= new Content();
    }

    /**
     * @param array $content
     * @throws InvalidConfigException
     */
    public function setContent(array $content)
    {
        $this->setType(self::TYPE_CONTENT);
        $this->content = new Content($content);
    }

    /**
     * @return Data
     */
    public function getData(): Data
    {
        return $this->data ??= new Data();
    }

    /**
     * @param array $data
     * @throws InvalidConfigException
     */
    public function setData(array $data)
    {
        $this->setType(self::TYPE_DATA);
        $this->data = new Data($data);
    }
}