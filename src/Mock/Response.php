<?php declare(strict_types=1);

namespace lav45\MockServer\Mock;

use lav45\MockServer\Component\DTObject;
use lav45\MockServer\Mock\Response\Content;
use lav45\MockServer\Mock\Response\Data;
use lav45\MockServer\Mock\Response\Proxy;

class Response extends DTObject
{
    use DataTypeTrait;

    public const string TYPE_CONTENT = 'content';
    public const string TYPE_PROXY = 'proxy';
    public const string TYPE_DATA = 'data';

    public float $delay = 0;
    private Content $content;
    private Proxy $proxy;
    private Data $data;

    public function getProxy(): Proxy
    {
        return $this->proxy ??= new Proxy();
    }

    public function setProxy(array $proxy): void
    {
        $this->setType(self::TYPE_PROXY);
        $this->proxy = new Proxy($proxy);
    }

    public function getContent(): Content
    {
        return $this->content ??= new Content();
    }

    public function setContent(array $content): void
    {
        $this->setType(self::TYPE_CONTENT);
        $this->content = new Content($content);
    }

    public function getData(): Data
    {
        return $this->data ??= new Data();
    }

    public function setData(array $data): void
    {
        $this->setType(self::TYPE_DATA);
        $this->data = new Data($data);
    }
}