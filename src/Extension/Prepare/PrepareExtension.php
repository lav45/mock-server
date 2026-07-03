<?php declare(strict_types=1);

namespace Lav45\MockServer\Extension\Prepare;

use Lav45\MockServer\Extension\Middleware;
use Lav45\MockServer\Extension\MiddlewareFactory;
use Lav45\MockServer\Parser\VariableParser;
use Psr\Container\ContainerInterface;

final readonly class PrepareExtension implements MiddlewareFactory
{
    public function create(ContainerInterface $container, array $config): Middleware
    {
        return new PrepareMiddleware(
            parserFactory: new ParserFactory(
                $container->get(VariableParser::class),
            ),
        );
    }
}
