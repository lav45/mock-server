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
use JsonException;
use lav45\MockServer\middlewares\RequestParamsMiddleware;
use lav45\MockServer\middlewares\WebhooksMiddleware;
use Monolog\Logger;
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
        private readonly ErrorHandler $errorHandler,
        private readonly FakerParser  $faker,
        private readonly Logger       $logger
    )
    {
        $this->mocksPath = rtrim($mocksPath, '/');
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function handleRequest(Request $request): Response
    {
        try {
            $started = microtime(true);
            $requestNum = $this->getRequestNum();
            $this->logger->info($this->getMessage($requestNum, $request));

            $response = $this->handleRequestInternal($request);
            $request->getBody()->close();

            $duration = microtime(true) - $started;
            $this->logger->debug($this->getMessage($requestNum, $request, $duration));
        } catch (\Throwable $e) {
            $error = "{$e->getMessage()} at {$e->getFile()}:{$e->getLine()}\n{$e->getTraceAsString()}";
            $this->logger->error($error);
            $response = $this->errorHandler->handleError(HttpStatus::INTERNAL_SERVER_ERROR, null, $request);
        }

        return $response;
    }

    /**
     * @param Request $request
     * @return Response
     * @throws JsonException
     */
    protected function handleRequestInternal(Request $request): Response
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

    /**
     * @param array $match
     * @param Request $request
     * @return Response
     */
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

    /**
     * @return int
     */
    private function getRequestNum(): int
    {
        static $requestNum = 0;
        return ++$requestNum;
    }

    /**
     * @param int $num
     * @param Request $request
     * @param float|null $seconds
     * @return string
     */
    private function getMessage(int $num, Request $request, float $seconds = null): string
    {
        $uri = $request->getUri();
        $queryString = $uri->getQuery();
        $queryString = $queryString ? '?' . $queryString : '';
        if ($seconds) {
            $seconds = round($seconds * 1000);
        }
        $finished = $seconds ? " finished in {$seconds} ms" : '';
        return sprintf(
            "#%03u %s %s%s%s",
            $num,
            $request->getMethod(),
            $uri->getPath(),
            $queryString,
            $finished
        );
    }

    /**
     * @param string $uri
     * @return string|null
     */
    private function getFile(string $uri): ?string
    {
        if (str_contains($uri, '?')) {
            $uri = strstr($uri, '?', true);
        }

        if ($uri === '/') {
            $uri = '/index';
        } else {
            $uri = rtrim($uri, '/');
        }

        $file = "{$this->mocksPath}{$uri}.json";
        if (file_exists($file) === false) {
            $uri = dirname($uri);
            if ($uri === '/') {
                return null;
            }
            return $this->getFile($uri);
        }
        return $file;
    }

    /**
     * @param string $file
     * @return array
     * @throws JsonException
     */
    private function getItems(string $file): array
    {
        $content = file_get_contents($file);
        return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @param RouteCollector $rc
     * @param array $item
     * @throws InvalidConfigException
     */
    private function addRoute(RouteCollector $rc, array $item)
    {
        $mock = new Mock($item);
        $request = $mock->getRequest();
        $response = $mock->getResponse();
        $webhooks = $mock->getWebhooks();

        $parser = new EnvParser($this->faker);
        $parser->addData([
            'env' => $parser->replaceFaker($mock->env)
        ]);

        $requestHandler = Middleware\stack(
            new RequestHandler($response, $parser),
            new WebhooksMiddleware($webhooks, $this->logger, $parser),
            new RequestParamsMiddleware($parser)
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