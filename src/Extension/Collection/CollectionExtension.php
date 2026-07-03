<?php declare(strict_types=1);

namespace Lav45\MockServer\Extension\Collection;

use Lav45\MockServer\DataFactory\DataBuilder;
use Lav45\MockServer\Extension\ExtensionProvider;
use Lav45\MockServer\Extension\ExtensionType;
use Lav45\MockServer\Extension\Middleware;
use Psr\Container\ContainerInterface;

final readonly class CollectionExtension implements ExtensionProvider
{
    public function type(): ExtensionType
    {
        return ExtensionType::Application;
    }

    public function create(ContainerInterface $container, array $config): Middleware
    {
        return new CollectionMiddleware(
            factory: new CollectionFactory($container->get(DataBuilder::class)),
            responder: new CollectionResponder(),
        );
    }
}
