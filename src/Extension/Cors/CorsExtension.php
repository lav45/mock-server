<?php declare(strict_types=1);

namespace Lav45\MockServer\Extension\Cors;

use Lav45\MockServer\Extension\ExtensionProvider;
use Lav45\MockServer\Extension\ExtensionType;
use Lav45\MockServer\Extension\Middleware;
use Psr\Container\ContainerInterface;

final readonly class CorsExtension implements ExtensionProvider
{
    public function type(): ExtensionType
    {
        return ExtensionType::System;
    }

    public function create(ContainerInterface $container, array $config): Middleware
    {
        return new CorsMiddleware(
            new CorsConfig(
                origins: $this->toList($config['allowOrigin'] ?? '*'),
                allowMethods: $this->toList($config['allowMethods'] ?? '*'),
                allowHeaders: $this->toList($config['allowHeaders'] ?? '*'),
                exposeHeaders: $this->toExposeHeaders($config),
                allowCredentials: (bool)($config['allowCredentials'] ?? false),
                maxAge: isset($config['maxAge']) ?? \is_numeric($config['maxAge']) ? (int)$config['maxAge'] : null,
            ),
        );
    }

    /**
     * @return list<string>
     */
    private function toList(string|array $value): array
    {
        if (\is_string($value)) {
            $value = \explode(',', $value);
        }
        return $value
            |> (static fn(array $parts) => \array_map(static fn(string $s) => \trim($s), $parts))
            |> \array_filter(...)
            |> \array_values(...);
    }

    /**
     * @param array<string, mixed> $config
     * @return list<string>|null
     */
    private function toExposeHeaders(array $config): array|null
    {
        if (\array_key_exists('exposeHeaders', $config) === false) {
            return ['*'];
        }
        if ($config['exposeHeaders'] === null) {
            return null;
        }
        $list = $this->toList($config['exposeHeaders']);
        return $list === [] ? null : $list;
    }
}
