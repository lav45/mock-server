<?php declare(strict_types=1);

namespace Lav45\MockServer\Extension\Routing;

use FastRoute\Dispatcher;
use Lav45\MockServer\Engine\Http\RequestHandler;
use Lav45\MockServer\Engine\Http\ServerRequest;
use Lav45\MockServer\Engine\Http\ServerResponse;
use Lav45\MockServer\Extension\Middleware;

final readonly class RoutingMiddleware implements Middleware
{
    public function __construct(
        private ErrorHandler $errorHandler,
        private Dispatcher   $dispatcher,
    ) {}

    public function process(ServerRequest $request, RequestHandler $next): ServerResponse
    {
        $match = $this->dispatcher->dispatch(
            $request->getMethod(),
            $request->getPath(),
        );

        return match ($match[0]) {
            Dispatcher::FOUND => $this->makeFoundResponse($request, $next, $match[1], $match[2]),
            Dispatcher::NOT_FOUND => $this->makeNotFoundResponse(),
            Dispatcher::METHOD_NOT_ALLOWED => $this->makeMethodNotAllowedResponse($match[1]),
        };
    }

    private function makeFoundResponse(ServerRequest $request, RequestHandler $next, array $data, array $params): ServerResponse
    {
        $request->setAttribute('data', $data);
        $request->setAttribute('params', $params);
        return $next->handleRequest($request);
    }

    private function makeNotFoundResponse(): ServerResponse
    {
        return $this->errorHandler->handleError(404);
    }

    private function makeMethodNotAllowedResponse(array $methods): ServerResponse
    {
        $response = $this->errorHandler->handleError(405);
        $response->setHeader('allow', \implode(', ', $methods));
        return $response;
    }
}
