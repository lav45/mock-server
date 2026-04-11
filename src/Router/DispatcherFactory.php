<?php declare(strict_types=1);

namespace Lav45\MockServer\Router;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Lav45\MockServer\Domain\Mock\Request;
use Lav45\MockServer\Http\RequestFactory;
use Psr\Log\LoggerInterface;

use function FastRoute\simpleDispatcher;

final readonly class DispatcherFactory implements Watcher\DispatcherFactory
{
    public function __construct(
        private RequestFactory  $requestFactory,
        private LoggerInterface $logger,
        private array           $options = [],
    ) {}

    public function create(iterable $data): Dispatcher
    {
        $routeDefinitionCallback = function (RouteCollector $router) use ($data): void {
            foreach ($data as $mocks) {
                foreach ($mocks as $mock) {
                    try {
                        $request = Request::fromArray($mock['request'] ?? []);
                        $handler = $this->requestFactory->withData($mock);
                    } catch (\Throwable $exception) {
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
