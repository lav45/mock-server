<?php declare(strict_types=1);

namespace Lav45\MockServer;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;

use function FastRoute\simpleDispatcher;

final readonly class DispatcherFactory implements DispatcherFactoryInterface
{
    public function __construct(
        private RequestFactoryInterface $requestFactory,
    ) {}

    public function create(iterable $data): Dispatcher
    {
        return simpleDispatcher(function (RouteCollector $router) use ($data): void {
            foreach ($data as $mocks) {
                foreach ($mocks as $mock) {
                    $router->addRoute(
                        $mock['request']['method'] ?? ['GET'],
                        $mock['request']['url'] ?? '/',
                        $this->requestFactory->create($mock),
                    );
                }
            }
        });
    }
}
