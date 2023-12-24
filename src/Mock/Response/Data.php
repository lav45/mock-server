<?php declare(strict_types=1);

namespace lav45\MockServer\Mock\Response;

use lav45\MockServer\Component\DTObject;
use lav45\MockServer\Mock\DataTypeTrait;
use lav45\MockServer\Mock\Response\Data\Pagination;

class Data extends DTObject
{
    use DataTypeTrait;

    public const string TYPE_JSON = 'json';
    public const string TYPE_FILE = 'file';

    public int $status = 200;
    private array $headers = ['content-type' => 'application/json'];
    private array $json = [];
    private Pagination $pagination;
    public string|array $result = '{{response.data.items}}';

    public function getPagination(): Pagination
    {
        return $this->pagination ??= new Pagination();
    }

    public function setPagination(array $pagination): void
    {
        $this->pagination = new Pagination($pagination);
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function setHeaders(array $headers): void
    {
        foreach ($headers as $key => $value) {
            $this->headers[$key] = $value;
        }
    }

    public function getJson(): array
    {
        return $this->json;
    }

    public function setJson(array $json): void
    {
        $this->setType(self::TYPE_JSON);
        $this->json = $json;
    }

    public function setFile(string $file): void
    {
        $this->setType(self::TYPE_FILE);
        $content = file_get_contents($file);
        $this->json = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    }
}