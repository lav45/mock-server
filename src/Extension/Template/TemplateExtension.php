<?php declare(strict_types=1);

namespace Lav45\MockServer\Extension\Template;

use Lav45\MockServer\Extension\ExtensionProvider;
use Lav45\MockServer\Extension\ExtensionType;
use Lav45\MockServer\Extension\Middleware;
use Psr\Container\ContainerInterface;

final readonly class TemplateExtension implements ExtensionProvider
{
    public function type(): ExtensionType
    {
        return ExtensionType::Application;
    }

    public function create(ContainerInterface $container, array $config): Middleware
    {
        return new TemplateMiddleware(
            resolver: new TemplateResolver($config['template'] ?? []),
        );
    }
}
