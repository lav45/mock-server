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
        private LoggerInterface $logger = new NullLogger(),
        private array           $options = [],
    ) {}

    public function create(iterable $mocks): Dispatcher
    {
        $routeDefinitionCallback = function (RouteCollector $router) use ($mocks): void {
            foreach ($mocks as $mock) {
                try {
                    $request = Request::fromArray($mock['request'] ?? []);
                } catch (\Throwable $exception) {
                    $this->logger->error($exception);
                    continue;
                }
                $router->addRoute(
                    $request->methods->toArray(),
                    $request->path->value,
                    $mock,
                );
                $this->logger->debug(\sprintf(
                    'Added route: [%s] %s',
                    \implode(',', $request->methods->toArray()),
                    $request->path->value,
                ));

                // @codeCoverageIgnoreStart
                if (isset($mock['request']['url'])) { // TODO deprecated
                    $this->logger->warning('The parameter "request.url" is deprecated since 4.1.1 and will be removed in 5.0.0. Please use "request.path" instead or run `bin/upgrade` to update your data.');
                }
                if (isset($mock['response']['json'])) { // TODO deprecated
                    switch ($mock['response']['type'] ?? 'content') {
                        case 'content':
                            $this->logger->warning('The parameter "response(type=content).json" is deprecated since 4.3.1 and will be removed in 5.0.0. Please use "response.body" instead or run `bin/upgrade` to update your data.');
                            break;
                        case 'data':
                            $this->logger->warning('The parameter "response(type=data).json" is deprecated since 4.3.1 and will be removed in 5.0.0. Please use "response.items" instead or run `bin/upgrade` to update your data.');
                            break;
                    }
                }
                if (isset($mock['response']['text'])) { // TODO deprecated
                    $this->logger->warning('The parameter "response(type=content).text" is deprecated since 4.3.1 and will be removed in 5.0.0. Please use "response.body" instead or run `bin/upgrade` to update your data.');
                }
                if (isset($mock['response']['type']) === false || $mock['response']['type'] === 'content') { // TODO deprecated
                    $withJson = isset($mock['response']['json'])
                        || (isset($mock['response']['body']) && \is_array($mock['response']['body']));
                    if ($withJson && isset($mock['response']['headers']['content-type']) === false) {
                        $this->logger->warning('Auto-adding "content-type: application/json" for response is deprecated since 4.3.1 and will be removed in 5.0.0. Please set "response.headers.content-type" explicitly or run `bin/upgrade` to update your data.');
                    }
                }
                if (isset($mock['response']['type']) && $mock['response']['type'] === 'proxy') { // TODO deprecated
                    $withJson = isset($mock['response']['content']) && \is_array($mock['response']['content']);
                    if ($withJson && isset($mock['response']['headers']['content-type']) === false) {
                        $this->logger->warning('Auto-adding "content-type: application/json" for proxy response is deprecated since 4.3.1 and will be removed in 5.0.0. Please set "response.headers.content-type" explicitly or run `bin/upgrade` to update your data.');
                    }
                }
                if (isset($mock['webhooks'])) { // TODO deprecated
                    if (\array_column($mock['webhooks'], 'text')) {
                        $this->logger->warning('The parameter "webhooks[].text" is deprecated since 4.3.1 and will be removed in 5.0.0. Please use "webhooks[].body" instead or run `bin/upgrade` to update your data.');
                    }
                    if (\array_column($mock['webhooks'], 'json')) {
                        $this->logger->warning('The parameter "webhooks[].json" is deprecated since 4.3.1 and will be removed in 5.0.0. Please use "webhooks[].body" instead or run `bin/upgrade` to update your data.');
                    }
                    foreach ($mock['webhooks'] as $webhook) {
                        $withJson = isset($webhook['json'])
                            || (isset($webhook['body']) && \is_array($webhook['body']));
                        if ($withJson && isset($webhook['headers']['content-type']) === false) {
                            $this->logger->warning('Auto-adding "content-type: application/json" for webhooks is deprecated since 4.3.1 and will be removed in 5.0.0. Please set "webhooks[].headers.content-type" explicitly or run `bin/upgrade` to update your data.');
                        }
                    }
                }
                // @codeCoverageIgnoreEnd
            }
        };
        return simpleDispatcher($routeDefinitionCallback, $this->options);
    }
}
