<?php declare(strict_types=1);

namespace Lav45\MockServer\Extension;

final readonly class Extension
{
    public function __construct(
        public string      $class,
        public array       $config = [],
        public string|null $schema = null,
    ) {}

    public static function fromArray(array $array): self
    {
        return new self(
            class: $array['class'],
            config: $array['config'] ?? [],
            schema: $array['schema'] ?? null,
        );
    }
}
