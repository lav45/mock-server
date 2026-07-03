<?php declare(strict_types=1);

namespace Lav45\MockServer\Bootstrap;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Lav45\MockServer\Domain\Request;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

use function FastRoute\simpleDispatcher;

final readonly class DispatcherFactory
{
    public function __construct(
        private \Closure        $handler,
        private LoggerInterface $logger = new NullLogger(),
        private array           $options = [],
    ) {}

    public function create(iterable $mocks): Dispatcher
    {
        $routeDefinitionCallback = function (RouteCollector $router) use ($mocks): void {
            foreach ($mocks as $source => $mock) {
                try {
                    $data = ($this->handler)($mock);

                    $request = Request::fromArray($data['request'] ?? []);

                    $router->addRoute(
                        $request->methods->toArray(),
                        $request->path->value,
                        $data,
                    );

                    $this->logger->debug(\sprintf('Added route: [%s] %s', $request->methods->toString(), $request->path->value));
                } catch (\Throwable $exception) {
                    $this->logger->error(\sprintf('%s: %s', $source, $exception->getMessage()));
                    continue;
                }
            }
        };
        return simpleDispatcher($routeDefinitionCallback, $this->options);
    }
}
