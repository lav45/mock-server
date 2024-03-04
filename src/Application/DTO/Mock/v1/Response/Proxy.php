<?php declare(strict_types=1);

namespace lav45\MockServer\Application\DTO\Mock\v1\Response;

/**
 * @codeCoverageIgnore
 */
final readonly class Proxy
{
    public function __construct(
        public string $url,
        /** @var array|string|null */
        public mixed  $content = null,
        public array  $headers = [],
        public array  $options = [],
    )
    {
    }
}