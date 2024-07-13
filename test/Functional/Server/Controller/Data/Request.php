<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Functional\Server\Controller\Data;

final readonly class Request
{
    public float $time;

    public function __construct(
        public string $method,
        public array $get,
        public array $post,
        public array $headers,
    ) {
        $this->time = \microtime(true);
    }
}
