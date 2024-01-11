<?php declare(strict_types=1);

namespace lav45\MockServer;

use Amp\Http\HttpStatus;
use Amp\Http\Server\ErrorHandler;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler as RequestHandlerInterface;
use Amp\Http\Server\Response;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use lav45\MockServer\Mock\Mock;
use lav45\MockServer\Request\Handler\RequestHandler;
use Psr\Log\LoggerInterface;
use function FastRoute\simpleDispatcher;
use function implode;
use function rawurldecode;

class Reactor implements RequestHandlerInterface
{
    private string $mocksPath;
    private array $routerCache = [];

    public function __construct(
        string                           $mocksPath,
        private readonly ErrorHandler    $errorHandler,
        private readonly FakerParser     $faker,
        private readonly LoggerInterface $logger,
        private readonly HttpClient      $httpClient,
    )
    {
        $this->mocksPath = rtrim($mocksPath, '/');
    }

    public function handleRequest(Request $request): Response
    {
        $method = $request->getMethod();
        $uri = rawurldecode($request->getUri()->getPath());

        $file = $this->getFile($uri);
        if ($file === null) {
            return $this->makeNotFoundResponse($request);
        }

        $match = $this->getRouter($file)->dispatch($method, $uri);

        return $this->matchRequest($match, $request);
    }

    protected function getRouter(string $file): Dispatcher
    {
        $key = md5_file($file);
        if (isset($this->routerCache[$file]) && $this->routerCache[$file][0] === $key) {
            return $this->routerCache[$file][1];
        }

        $routes = $this->getRoutes($file);
        $dispatcher = simpleDispatcher(function (RouteCollector $router) use ($routes): void {
            foreach ($routes as $item) {
                $this->addRoute($router, new Mock($item));
            }
        });

        $this->routerCache[$file] = [$key, $dispatcher];

        return $dispatcher;
    }

    private function matchRequest(array $match, Request $request): Response
    {
        return match ($match[0]) {
            Dispatcher::FOUND => $this->makeFoundResponse($match, $request),
            Dispatcher::NOT_FOUND => $this->makeNotFoundResponse($request),
            Dispatcher::METHOD_NOT_ALLOWED => $this->makeMethodNotAllowedResponse($match[1], $request),
        };
    }

    private function getFile(string $uri): string|null
    {
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

    private function getRoutes(string $file): array
    {
        $content = file_get_contents($file);
        return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    }

    private function addRoute(RouteCollector $router, Mock $mock): void
    {
        $request = $mock->getRequest();
        $response = $mock->getResponse();
        $webhooks = $mock->getWebhooks();

        $requestHandler = new RequestHandler(
            $response,
            $webhooks,
            $this->faker,
            $mock->env,
            $this->logger,
            $this->httpClient
        );

        $router->addRoute($request->getMethod(), $request->url, $requestHandler);
    }

    private function makeFoundResponse(array $match, Request $request): Response
    {
        /**
         * @var RequestHandlerInterface $requestHandler
         * @var string[] $routeArgs
         */
        [, $requestHandler, $routeArgs] = $match;
        $request->setAttribute(self::class, $routeArgs);
        return $requestHandler->handleRequest($request);
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
     */
    private function makeMethodNotAllowedResponse(array $methods, Request $request): Response
    {
        $response = $this->errorHandler->handleError(HttpStatus::METHOD_NOT_ALLOWED, null, $request);
        $response->setHeader('allow', implode(', ', $methods));
        return $response;
    }
}