<?php declare(strict_types=1);

namespace Lav45\MockServer\Bootstrap;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Lav45\MockServer\Domain\Request;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

use function FastRoute\simpleDispatcher;

final readonly class DispatcherFactory implements Watcher\DispatcherFactory
{
    public function __construct(
        private LoggerInterface $logger = new NullLogger(),
        private array           $options = [],
    ) {}

    public function create(array $data): Dispatcher
    {
        $routeDefinitionCallback = function (RouteCollector $router) use ($data): void {
            foreach ($data as $mocks) {
                foreach ($mocks as $mock) {
                    try {
                        $request = Request::fromArray($mock['request'] ?? []);
                    } catch (\Throwable $exception) {
                        $this->logger->error($exception);
                        continue;
                    }
                    $router->addRoute(
                        $request->methods->toArray(),
                        $request->url->value,
                        $mock,
                    );
                    $this->logger->debug(\sprintf(
                        'Added route: [%s] %s',
                        \implode(',', $request->methods->toArray()),
                        $request->url->value,
                    ));
                }
            }
        };
        return simpleDispatcher($routeDefinitionCallback, $this->options);
    }
}
