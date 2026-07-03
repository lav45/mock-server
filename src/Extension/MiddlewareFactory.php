<?php declare(strict_types=1);

namespace Lav45\MockServer\Extension;

use Psr\Container\ContainerInterface;

interface MiddlewareFactory
{
    public function create(ContainerInterface $container, array $config): Middleware;
}
