<?php declare(strict_types=1);

namespace Lav45\MockServer\Application\Data\Mock\v1\Response;

/**
 * @codeCoverageIgnore
 */
final readonly class Proxy
{
    public function __construct(
        public string            $url,
        public array|string|null $content = null,
        public array             $headers = [],
        public array             $options = [],
    ) {}
}
