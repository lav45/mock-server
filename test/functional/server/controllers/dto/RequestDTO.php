<?php declare(strict_types=1);

namespace lav45\MockServer\test\functional\server\controllers\dto;

readonly class RequestDTO
{
    public float $time;

    public function __construct(
        public string $method,
        public array $get,
        public array $post,
        public array $headers,
    )
    {
        $this->time = microtime(true);
    }
}