<?php declare(strict_types=1);

namespace Lav45\MockServer\Extension\Direct;

use Lav45\MockServer\DataFactory\DataBuilder;
use Lav45\MockServer\Engine\HttpClient;
use Lav45\MockServer\Extension\ExtensionProvider;
use Lav45\MockServer\Extension\ExtensionType;
use Lav45\MockServer\Extension\Middleware;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

final readonly class DirectExtension implements ExtensionProvider
{
    public function type(): ExtensionType
    {
        return ExtensionType::Application;
    }

    public function create(ContainerInterface $container, array $config): Middleware
    {
        return new DirectMiddleware(
            factory: new DirectFactory($container->get(DataBuilder::class)),
            handler: new DirectHandler(
                httpClient: $container->get(HttpClient::class)->withLabel('Direct'),
                logger: $container->get(LoggerInterface::class),
            ),
        );
    }
}
