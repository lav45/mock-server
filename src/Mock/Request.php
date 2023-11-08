<?php declare(strict_types=1);

namespace lav45\MockServer\Mock;

use lav45\MockServer\components\DTObject;

class Request extends DTObject
{
    private array $method = ['GET'];
    public string $url = '/';

    public function getMethod(): array
    {
        return $this->method;
    }

    public function setMethod(array|string $method): void
    {
        $this->method = (array)$method;
    }
}