<?php declare(strict_types=1);

namespace Lav45\MockServer\Extension\WebHook;

use Lav45\MockServer\DataFactory\DataBuilder;
use Lav45\MockServer\Engine\WebHookQueue;
use Lav45\MockServer\Extension\ExtensionProvider;
use Lav45\MockServer\Extension\ExtensionType;
use Lav45\MockServer\Extension\Middleware;
use Psr\Container\ContainerInterface;

final readonly class WebHookExtension implements ExtensionProvider
{
    public function type(): ExtensionType
    {
        return ExtensionType::Application;
    }

    public function create(ContainerInterface $container, array $config): Middleware
    {
        return new WebHookMiddleware(
            factory: new WebHooksFactory($container->get(DataBuilder::class)),
            queue: $container->get(WebHookQueue::class),
        );
    }
}
