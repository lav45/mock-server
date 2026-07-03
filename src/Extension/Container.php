<?php declare(strict_types=1);

namespace Lav45\MockServer\Extension;

use Psr\Container\ContainerInterface;

final readonly class Container implements ContainerInterface
{
    /**
     * @param array<class-string, object> $services
     */
    public function __construct(
        private array $services = [],
    ) {}

    public function get(string $id): object
    {
        return $this->services[$id] ?? throw new NotFoundException("Service not found: {$id}");
    }

    public function has(string $id): bool
    {
        return isset($this->services[$id]);
    }
}
