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
        private \Closure        $migrate,
        private LoggerInterface $logger = new NullLogger(),
        private array           $options = [],
    ) {}

    public function create(iterable $mocks): Dispatcher
    {
        $routeDefinitionCallback = function (RouteCollector $router) use ($mocks): void {
            $deprecated = false;
            foreach ($mocks as $mock) {
                $data = ($this->migrate)($mock);
                if ($deprecated === false && $data !== $mock) {
                    $deprecated = true;
                    $this->logger->warning('Deprecated mock format detected and migrated on the fly. Please run `bin/migrate` to update your mock files.');
                }
                try {
                    $request = Request::fromArray($data['request'] ?? []);
                } catch (\Throwable $exception) {
                    $this->logger->error($exception);
                    continue;
                }
                $router->addRoute(
                    $request->methods->toArray(),
                    $request->path->value,
                    $data,
                );
                $this->logger->debug(\sprintf(
                    'Added route: [%s] %s',
                    \implode(',', $request->methods->toArray()),
                    $request->path->value,
                ));
            }
        };
        return simpleDispatcher($routeDefinitionCallback, $this->options);
    }
}
