<?php declare(strict_types=1);

namespace lav45\MockServer\Application\DTO\Mock\v1;

/**
 * @codeCoverageIgnore
 */
final readonly class Webhook
{
    public function __construct(
        public string      $url,
        public mixed       $delay = 0,
        public string      $method = 'POST',
        public array       $headers = [],
        public array|null  $json = null,
        public string|null $text = null,
        public array       $options = [],
    ) {}
}
