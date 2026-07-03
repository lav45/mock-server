<?php declare(strict_types=1);

namespace Lav45\MockServer\Extension\Routing;

use FastRoute\Dispatcher;
use Lav45\MockServer\Extension\Middleware;
use Lav45\MockServer\Extension\MiddlewareFactory;
use Psr\Container\ContainerInterface;

final readonly class RoutingExtension implements MiddlewareFactory
{
    public function create(ContainerInterface $container, array $config): Middleware
    {
        return new RoutingMiddleware(
            errorHandler: new ErrorHandler(),
            dispatcher: $container->get(Dispatcher::class),
        );
    }
}
