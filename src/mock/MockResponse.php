<?php

namespace lav45\MockServer\mock;

use lav45\MockServer\components\DTObject;
use lav45\MockServer\InvalidConfigException;

/**
 * Class MockResponse
 * @package lav45\MockServer\mock
 */
class MockResponse extends DTObject
{
    /** @var float Number of seconds to wait. */
    public float $delay = 0;
    /** @var MockResponseContent */
    private $content;
    /** @var MockResponseProxy */
    private $proxy;

    /**
     * @return MockResponseProxy
     */
    public function getProxy(): MockResponseProxy
    {
        return $this->proxy ??= new MockResponseProxy();
    }

    /**
     * @param array $proxy
     */
    public function setProxy(array $proxy)
    {
        $this->proxy = new MockResponseProxy($proxy);
    }

    /**
     * @return MockResponseContent
     */
    public function getContent(): MockResponseContent
    {
        return $this->content ??= new MockResponseContent();
    }

    /**
     * @param array $content
     * @throws InvalidConfigException
     */
    public function setContent(array $content)
    {
        if ($this->proxy) {
            throw new InvalidConfigException("You can't use `content` and `proxy` at the same time");
        }
        $this->content = new MockResponseContent($content);
    }
}