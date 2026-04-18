<?php declare(strict_types=1);

namespace Lav45\MockServer\Bootstrap;

use Amp\Http\HttpStatus;
use Amp\Http\Server\ErrorHandler;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\Response;
use FastRoute\Dispatcher;

final readonly class RouterRequestHandler implements RequestHandler
{
    public function __construct(
        private ErrorHandler   $errorHandler,
        private Watcher        $watcher,
        private RequestHandler $handler,
    ) {}

    public function handleRequest(Request $request): Response
    {
        $match = $this->watcher->getDispatcher()->dispatch(
            $request->getMethod(),
            $request->getUri()->getPath(),
        );

        return match ($match[0]) {
            Dispatcher::FOUND => $this->makeFoundResponse($request, $match[1], $match[2]),
            Dispatcher::NOT_FOUND => $this->makeNotFoundResponse($request),
            Dispatcher::METHOD_NOT_ALLOWED => $this->makeMethodNotAllowedResponse($request, $match[1]),
        };
    }

    private function makeFoundResponse(Request $request, array $data, array $requestArgs): Response
    {
        $request->setAttribute('data', $data);
        $request->setAttribute('urlParams', $requestArgs);
        return $this->handler->handleRequest($request);
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
    private function makeMethodNotAllowedResponse(Request $request, array $methods): Response
    {
        $response = $this->errorHandler->handleError(HttpStatus::METHOD_NOT_ALLOWED, null, $request);
        $response->setHeader('allow', \implode(', ', $methods));
        return $response;
    }
}
