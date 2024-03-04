<?php declare(strict_types=1);

namespace lav45\MockServer\Application\DTO\Mock\v1\Response;

/**
 * @codeCoverageIgnore
 */
final readonly class Content
{
    public function __construct(
        public mixed      $status = 200,
        public array      $headers = [],
        public string     $text = '',
        public array|null $json = null,
    )
    {
    }
}