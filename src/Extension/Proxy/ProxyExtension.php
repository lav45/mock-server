<?php declare(strict_types=1);

namespace Lav45\MockServer\Extension\Proxy;

use Lav45\MockServer\DataFactory\DataBuilder;
use Lav45\MockServer\Engine\HttpClient;
use Lav45\MockServer\Extension\ExtensionProvider;
use Lav45\MockServer\Extension\ExtensionType;
use Lav45\MockServer\Extension\Middleware;
use Psr\Container\ContainerInterface;

final readonly class ProxyExtension implements ExtensionProvider
{
    public function type(): ExtensionType
    {
        return ExtensionType::Application;
    }

    public function create(ContainerInterface $container, array $config): Middleware
    {
        return new ProxyMiddleware(
            factory: new ProxyFactory($container->get(DataBuilder::class)),
            responder: new ProxyResponder(
                httpClient: $container->get(HttpClient::class)->withLabel('Proxy'),
            ),
        );
    }
}
