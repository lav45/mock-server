<?php declare(strict_types=1);

namespace Lav45\MockServer\Application\Data\Mock\v1;

/**
 * @codeCoverageIgnore
 */
final readonly class Webhook
{
    public function __construct(
        public string       $url,
        public float|string $delay = 0.0,
        public string       $method = 'POST',
        public array        $headers = [],
        public array|null   $json = null,
        public string|null  $text = null,
        public array        $options = [],
    ) {}
}
