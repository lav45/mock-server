<?php

namespace lav45\MockServer;

use Amp\Http\HttpStatus;
use Amp\Http\Server\ErrorHandler;
use Amp\Http\Server\Middleware;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler as RequestHandlerInterface;
use Amp\Http\Server\Response;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use lav45\MockServer\middlewares\ResponseDelayMiddleware;
use lav45\MockServer\middlewares\ResponseProxyMiddleware;
use lav45\MockServer\middlewares\WebhooksMiddleware;
use lav45\MockServer\mock\Mock;
use function FastRoute\simpleDispatcher;

/**
 * Class Router
 * @package lav45\MockServer
 */
class Router implements RequestHandlerInterface
{
    /** @var string */
    private string $mocksPath;

    public function __construct(
        string                        $mocksPath,
        private readonly ErrorHandler $errorHandler
    )
    {
        $this->mocksPath = rtrim($mocksPath, '/');
    }

    /**
     * @param Request $request
     * @return Response
     * @throws \JsonException
     */
    public function handleRequest(Request $request): Response
    {
        $method = $request->getMethod();
        $uri = \rawurldecode($request->getUri()->getPath());

        $file = $this->getFile($uri);
        if ($file === null) {
            return $this->makeNotFoundResponse($request);
        }

        $routeDispatcher = simpleDispatcher(function (RouteCollector $rc) use ($file): void {
            $items = $this->getItems($file);
            foreach ($items as $item) {
                $this->addRoute($rc, $item);
            }
        });

        $match = $routeDispatcher->dispatch($method, $uri);

        return $this->dispatch($match, $request);
    }

    private function dispatch(array $match, Request $request)
    {
        switch ($match[0]) {
            case Dispatcher::FOUND:
                /**
                 * @var RequestHandlerInterface $requestHandler
                 * @var string[] $routeArgs
                 */
                [, $requestHandler, $routeArgs] = $match;
                $request->setAttribute(self::class, $routeArgs);

                return $requestHandler->handleRequest($request);

            case Dispatcher::NOT_FOUND:
                return $this->makeNotFoundResponse($request);

            case Dispatcher::METHOD_NOT_ALLOWED:
                return $this->makeMethodNotAllowedResponse($match[1], $request);

            default:
                throw new \UnexpectedValueException("Encountered unexpected dispatcher code: " . $match[0]);
        }
    }

    private function getFile(string $uri): ?string
    {
        if (str_contains($uri, '?')) {
            $uri = strstr($uri, '?', true);
        }

        if ($uri === '/') {
            $file = '/index';
        } else {
            $file = $uri;
        }

        $file = "{$this->mocksPath}{$file}.json";
        if (file_exists($file) === false) {
            $uri = dirname($uri);
            if ($uri === "/") {
                return null;
            }
            return $this->getFile($uri);
        }
        return $file;
    }

    private function getItems(string $file): array
    {
        $content = file_get_contents($file);
        return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    }

    private function addRoute(RouteCollector $rc, array $item)
    {
        $mock = new Mock($item);
        $request = $mock->getRequest();
        $response = $mock->getResponse();
        $webhooks = $mock->getWebhooks();

        $requestHandler = Middleware\stack(
            new RequestHandler($response->getContent()),
            new ResponseDelayMiddleware($response),
            new ResponseProxyMiddleware($response->getProxy()),
            new WebhooksMiddleware($webhooks),
        );

        $rc->addRoute($request->getMethod(), $request->url, $requestHandler);
    }

    /**
     * Create a response if no routes matched and no fallback has been set.
     */
    private function makeNotFoundResponse(Request $request): Response
    {
        return $this->errorHandler->handleError(HttpStatus::NOT_FOUND, null, $request);
    }

    /**
     * Create a response if the requested method is not allowed for the matched path.
     * @param string[] $methods
     */
    private function makeMethodNotAllowedResponse(array $methods, Request $request): Response
    {
        $response = $this->errorHandler->handleError(HttpStatus::METHOD_NOT_ALLOWED, null, $request);
        $response->setHeader("allow", \implode(", ", $methods));
        return $response;
    }
}