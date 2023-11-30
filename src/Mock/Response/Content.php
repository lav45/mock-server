<?php declare(strict_types=1);

namespace lav45\MockServer\Mock\Response;

use lav45\MockServer\components\DTObject;
use lav45\MockServer\Mock\DataTypeTrait;

class Content extends DTObject
{
    use DataTypeTrait;

    public const TYPE_JSON = 'json';
    public const TYPE_TEXT = 'text';

    public int $status = 200;
    private array $headers = [];
    private string $text = '';
    private array $json = [];

    public function getJson(): array
    {
        return $this->json;
    }

    public function setJson(array $data): void
    {
        $this->setType(self::TYPE_JSON);
        $this->addHeader('content-type', 'application/json');
        $this->json = $data;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function setHeaders(array $headers): void
    {
        foreach ($headers as $key => $value) {
            $this->addHeader($key, $value);
        }
    }

    protected function addHeader(string $key, string $value): void
    {
        $this->headers[$key] = $value;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function setText(string $text): void
    {
        $this->setType(self::TYPE_TEXT);
        $this->text = $text;
    }
}