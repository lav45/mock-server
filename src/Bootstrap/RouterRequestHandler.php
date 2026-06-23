<?php declare(strict_types=1);

namespace Lav45\MockServer\Bootstrap;

use FastRoute\Dispatcher;
use Lav45\MockServer\Engine\Http\RequestHandler;
use Lav45\MockServer\Engine\Http\ServerRequest;
use Lav45\MockServer\Engine\Http\ServerResponse;
use Lav45\MockServer\Middleware\MiddlewareHandler;

final readonly class RouterRequestHandler implements RequestHandler
{
    public function __construct(
        private ErrorHandler      $errorHandler,
        private Dispatcher        $dispatcher,
        private MiddlewareHandler $handler,
    ) {}

    public function handleRequest(ServerRequest $request): ServerResponse
    {
        $match = $this->dispatcher->dispatch(
            $request->getMethod(),
            $request->getPath(),
        );

        return match ($match[0]) {
            Dispatcher::FOUND => $this->makeFoundResponse($request, $match[1], $match[2]),
            Dispatcher::NOT_FOUND => $this->makeNotFoundResponse(),
            Dispatcher::METHOD_NOT_ALLOWED => $this->makeMethodNotAllowedResponse($match[1]),
        };
    }

    private function makeFoundResponse(ServerRequest $request, array $data, array $params): ServerResponse
    {
        $request->setAttribute('data', $data);
        $request->setAttribute('params', $params);
        return $this->handler->handle($request);
    }

    /**
     * Create a response if no routes matched and no fallback has been set.
     */
    private function makeNotFoundResponse(): ServerResponse
    {
        return $this->errorHandler->handleError(404);
    }

    /**
     * Create a response if the requested method is not allowed for the matched path.
     */
    private function makeMethodNotAllowedResponse(array $methods): ServerResponse
    {
        $response = $this->errorHandler->handleError(405);
        $response->setHeader('allow', \implode(', ', $methods));
        return $response;
    }
}
