<?php declare(strict_types=1);

namespace Lav45\MockServer;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Lav45\MockServer\Domain\Model\Request;
use Psr\Log\LoggerInterface;

use function FastRoute\simpleDispatcher;

final readonly class DispatcherFactory implements Watcher\DispatcherFactory
{
    public function __construct(
        private RequestFactoryInterface $requestFactory,
        private LoggerInterface         $logger,
        private array                   $options = [],
    ) {}

    public function create(iterable $data): Dispatcher
    {
        $routeDefinitionCallback = function (RouteCollector $router) use ($data): void {
            foreach ($data as $mocks) {
                foreach ($mocks as $mock) {
                    try {
                        $request = Request::fromArray($mock['request']);
                        $handler = $this->requestFactory->create($mock);
                    } catch (\Exception $exception) {
                        $this->logger->error($exception);
                        continue;
                    }
                    $router->addRoute(
                        $request->methods,
                        $request->url,
                        $handler,
                    );
                }
            }
        };
        return simpleDispatcher($routeDefinitionCallback, $this->options);
    }
}
