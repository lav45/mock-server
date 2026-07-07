<?php declare(strict_types=1);

namespace Lav45\MockServer\Test\Unit\Components;

use Lav45\MockServer\Extension\ExtensionProvider;
use Lav45\MockServer\Extension\ExtensionType;
use Lav45\MockServer\Extension\Middleware;
use Psr\Container\ContainerInterface;

final readonly class ThrowingExtension implements ExtensionProvider
{
    public function type(): ExtensionType
    {
        return ExtensionType::System;
    }

    public function create(ContainerInterface $container, array $config): Middleware
    {
        throw new \RuntimeException('boom');
    }
}
